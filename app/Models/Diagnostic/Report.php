<?php

namespace App\Models\Diagnostic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $table = 'diagnostic_reports';
    
    protected $fillable = [
        'case_id', 'consultation_id', 'report_type',
        'summary', 'possible_causes', 'diagnostic_plan',
        'estimated_costs', 'recommended_actions', 'parts_list',
        'is_white_label', 'partner_name', 'partner_contacts'
    ];
    
    protected $casts = [
        'summary' => 'array',
        'possible_causes' => 'array',
        'diagnostic_plan' => 'array',
        'estimated_costs' => 'array',
        'recommended_actions' => 'array',
        'parts_list' => 'array',
        'partner_contacts' => 'array',
        'is_white_label' => 'boolean',
    ];
    
    public function case(): BelongsTo
    {
        return $this->belongsTo(DiagnosticCase::class);
    }
    
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }
    
    // Генерация отчёта в PDF
    public function generatePdf()
    {
        // TODO: Генерация PDF с помощью DomPDF или аналогичной библиотеки
        return null;
    }
    
    // Отправить отчёт по email
    public function sendToEmail(string $email): bool
    {
        // TODO: Реализация отправки email
        return true;
    }
    
    // Получить общую стоимость ремонта
    public function getTotalCost(): float
{
    $total = 0;
    
    if (is_array($this->estimated_costs)) {
        if (isset($this->estimated_costs['total'])) {
            $total = (float) $this->estimated_costs['total'];
        } elseif (is_array($this->estimated_costs)) {
            // Суммируем все числовые значения кроме 'note'
            foreach ($this->estimated_costs as $key => $value) {
                if ($key !== 'note' && is_numeric($value)) {
                    $total += (float) $value;
                }
            }
        }
    }
    
    return $total;
}

    // В модели Report добавим:
public function activeReport(): BelongsTo
{
    return $this->belongsTo(DiagnosticCase::class, 'case_id');
}

// В модели Case добавим:
///public function activeReport(): HasOne
//{
//    return $this->hasOne(Report::class, 'case_id')->latest();
//}

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
}