<?php

namespace App\Models\Diagnostic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DiagnosticCase extends Model
{
    protected $table = 'diagnostic_cases';
    
    // Используем UUID в качестве первичного ключа
   // protected $keyType = 'string';
    //public $incrementing = false;

    public $incrementing = true; // Включаем автоинкремент
   protected $keyType = 'int';  // Тип ключа - integer
    
    protected $fillable = [
        'id', // Добавляем id для UUID
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
        'price_estimate' => 'decimal:2',
        'time_estimate' => 'integer',
        'year' => 'integer',
        'mileage' => 'integer',
    ];
    
    //protected static function boot()
    //{
   //     parent::boot();
        
   //     static::creating(function ($model) {
    //        if (empty($model->id)) {
     //           $model->id = (string) \Illuminate\Support\Str::uuid();
    //        }
    //    });
   // }



      public static function boot()
    {
        parent::boot();
        
        static::updated(function ($case) {
            // При изменении статуса на report_ready создаем чат
            if ($case->isDirty('status') && $case->status === 'report_ready') {
                // Проверяем, есть ли уже активная консультация
                $activeConsultation = Consultation::where('case_id', $case->id)
                    ->whereIn('status', ['pending', 'in_progress', 'scheduled'])
                    ->first();
                    
                if (!$activeConsultation) {
                    // Создаем автоматическую консультацию
                    $consultation = Consultation::create([
                        'case_id' => $case->id,
                        'user_id' => $case->user_id,
                        'type' => 'expert',
                        'price' => $case->price_estimate ?? 3000,
                        'status' => 'pending',
                        'payment_status' => 'pending',
                        'is_auto_created' => true, // флаг автосоздания
                    ]);
                    
                    // Создаем системное сообщение
                    ConsultationMessage::create([
                        'consultation_id' => $consultation->id,
                        'user_id' => $case->user_id,
                        'message' => "Диагностический случай готов к консультации. Отчет сформирован автоматически.",
                        'type' => 'system',
                    ]);
                    
                    // Если есть отчет, прикрепляем его
                    if ($case->activeReport) {
                        ConsultationMessage::create([
                            'consultation_id' => $consultation->id,
                            'user_id' => $case->user_id,
                            'message' => "Сформирован отчет по диагностике",
                            'type' => 'report',
                            'metadata' => [
                                'report_id' => $case->activeReport->id,
                                'summary' => $case->activeReport->summary,
                            ],
                        ]);
                    }
                }
            }
        });
    }
    
   // public function activeReport()
 //   {
  //      return $this->hasOne(DiagnosticReport::class, 'case_id')->latest();
  //  }
    
    //public function consultations()
    //{
    //    return $this->hasMany(Consultation::class, 'case_id');
   // }
    
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
    
    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class);
    }
    
    public function brand(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Brand::class, 'brand_id', 'id');
    }
    
    public function model(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CarModel::class, 'model_id', 'id');
    }
    
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'case_id');
    }
    
   // public function activeReport(): HasOne
   // {
    //    return $this->hasOne(Report::class, 'case_id')->latest();
   // }
    
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'case_id');
    }
    
    // Получить тип рекомендуемой консультации
    public function getRecommendedConsultationType(): string
    {
        $complexity = $this->analysis_result['complexity_level'] ?? 1;
        
        if ($complexity >= 7) {
            return 'expert';
        } elseif ($complexity >= 4) {
            return 'premium';
        }
        
        return 'basic';
    }
    
    // Завершить анализ и создать отчет
    public function completeAnalysis(array $analysisResult): void
    {
        // Создаем отчет на основе анализа
        $report = $this->reports()->create([
            'report_type' => 'free',
            'summary' => [
                'Диагностика выполнена на основе выбранных симптомов',
                'Ориентировочное время диагностики: ' . ($analysisResult['estimated_time'] ?? 60) . ' минут',
                'Сложность проблемы: ' . ($analysisResult['complexity_level'] ?? 1) . '/10',
            ],
            'possible_causes' => $analysisResult['possible_causes'] ?? [],
            'diagnostic_plan' => $analysisResult['diagnostic_steps'] ?? [],
            'estimated_costs' => [
                'diagnostic' => 1500,
                'work' => $analysisResult['estimated_price'] ?? 3000,
                'total_parts' => $analysisResult['estimated_price'] ? $analysisResult['estimated_price'] * 2 : 6000,
                'total' => ($analysisResult['estimated_price'] ?? 3000) * 3,
                'note' => 'Цены ориентировочные, могут отличаться в зависимости от региона и конкретного сервиса',
            ],
            'recommended_actions' => [
                [
                    'title' => 'Выполнить первичную диагностику',
                    'description' => 'Следовать плану диагностики',
                    'priority' => 'high',
                ],
                [
                    'title' => 'При необходимости - обратиться к эксперту',
                    'description' => 'Заказать консультацию специалиста',
                    'priority' => 'medium',
                ],
            ],
        ]);
        
        $this->update([
            'status' => 'report_ready',
            'analysis_result' => $analysisResult,
            'price_estimate' => $analysisResult['estimated_price'] ?? 0,
            'time_estimate' => $analysisResult['estimated_time'] ?? 60,
        ]);
    }
    
    // Создать базовый отчет если его нет
    public function createBasicReport(): Report
{
    if ($this->reports()->exists()) {
        return $this->activeReport;
    }
    
    $priceEstimate = is_numeric($this->price_estimate) ? (float) $this->price_estimate : 3000;
    
    return $this->reports()->create([
        'report_type' => 'free',
        'summary' => [
            'Диагностика выполнена на основе выбранных симптомов',
            'Ориентировочное время диагностики: ' . ($this->time_estimate ?? 60) . ' минут',
            'Сложность проблемы: ' . (($this->analysis_result['complexity_level'] ?? 1) ?: 1) . '/10',
        ],
        'possible_causes' => $this->analysis_result['possible_causes'] ?? [
            'Необходима дополнительная диагностика',
            'Рекомендуется проверить электронные системы автомобиля',
        ],
        'diagnostic_plan' => $this->analysis_result['diagnostic_steps'] ?? [
            'Считать коды ошибок с помощью диагностического сканера',
            'Проверить основные датчики системы',
            'Провести визуальный осмотр моторного отсека',
        ],
        'estimated_costs' => [
            'diagnostic' => 1500,
            'work' => $priceEstimate,
            'total_parts' => $priceEstimate * 2,
            'total' => $priceEstimate * 3,
            'note' => 'Цены ориентировочные, могут отличаться в зависимости от региона и конкретного сервиса',
        ],
        'recommended_actions' => [
            [
                'title' => 'Выполнить первичную диагностику',
                'description' => 'Следовать плану диагностики',
                'priority' => 'high',
            ],
        ],
    ]);
}
}