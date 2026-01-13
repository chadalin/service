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
        return $this->belongsTo(Case::class);
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
        return $this->estimated_costs['total'] ?? 0;
    }
}