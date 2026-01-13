<?php

namespace App\Models\Diagnostic;

use App\Models\User;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Case extends Model
{
    use SoftDeletes;
    
    protected $table = 'diagnostic_cases';
    
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $fillable = [
        'user_id', 'rule_id', 'brand_id', 'model_id',
        'engine_type', 'year', 'vin', 'mileage',
        'symptoms', 'description', 'uploaded_files',
        'status', 'step', 'analysis_result',
        'price_estimate', 'time_estimate'
    ];
    
    protected $casts = [
        'symptoms' => 'array',
        'uploaded_files' => 'array',
        'analysis_result' => 'array',
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->id = (string) \Illuminate\Support\Str::uuid();
        });
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class);
    }
    
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
    
    public function model(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }
    
    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
    }
    
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
    
    public function scannerLogs(): HasMany
    {
        return $this->hasMany(ScannerLog::class);
    }
    
    // Получить активный отчёт
    public function activeReport()
    {
        return $this->reports()->latest()->first();
    }
    
    // Перейти к следующему шагу
    public function nextStep(): void
    {
        $this->increment('step');
        $this->save();
    }
    
    // Завершить анализ
    public function completeAnalysis(array $result): void
    {
        $this->update([
            'status' => 'report_ready',
            'analysis_result' => $result,
            'price_estimate' => $result['estimated_price'] ?? null,
            'time_estimate' => $result['estimated_time'] ?? null,
        ]);
    }
    
    // Получить рекомендуемый тип консультации
    public function getRecommendedConsultationType(): string
    {
        $complexity = $this->rule->complexity_level ?? 1;
        
        if ($complexity >= 7) {
            return 'expert';
        } elseif ($complexity >= 4) {
            return 'premium';
        }
        
        return 'basic';
    }
}