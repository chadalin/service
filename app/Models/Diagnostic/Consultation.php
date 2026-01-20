<?php

namespace App\Models\Diagnostic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Consultation extends Model
{
    protected $table = 'diagnostic_consultations';
    
    protected $fillable = [
        'case_id', 'user_id', 'expert_id',
        'type', 'price', 'status',
        'scheduled_at', 'duration',
        'payment_id', 'payment_status', 'paid_at',
        'expert_notes', 'customer_feedback', 'rating',
        'expert_analysis', 'recommendations',
        'questions_asked', 'answers_provided',
        'additional_data_requested',
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
        'scheduled_at' => 'datetime',
        'paid_at' => 'datetime',
        'duration' => 'integer',
        'rating' => 'integer',
        'expert_analysis' => 'array',
        'recommendations' => 'array',
        'questions_asked' => 'array',
        'answers_provided' => 'array',
        'additional_data_requested' => 'array',
    ];
    
    protected $appends = [
        'formatted_price',
        'status_label',
        'type_label',
        'is_updatable',
        'can_start',
    ];
    
    public function case(): BelongsTo
    {
        return $this->belongsTo(DiagnosticCase::class, 'case_id');
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
    
    public function expert(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'expert_id');
    }
    
    public function messages(): HasMany
    {
        return $this->hasMany(ConsultationMessage::class);
    }
    
    public function attachments(): HasMany
    {
        return $this->hasMany(ConsultationAttachment::class);
    }
    
    // Accessors
    public function getFormattedPriceAttribute(): string
    {
        return number_format(floatval($this->price), 0, '', ' ') . ' ₽';
    }
    
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'Ожидает оплаты',
            'scheduled' => 'Запланирована',
            'in_progress' => 'В процессе',
            'completed' => 'Завершена',
            'cancelled' => 'Отменена',
        ];
        
        return $labels[$this->status] ?? $this->status;
    }
    
    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'basic' => 'Базовая',
            'premium' => 'Премиум',
            'expert' => 'Экспертная',
        ];
        
        return $labels[$this->type] ?? $this->type;
    }
    
    public function getIsUpdatableAttribute(): bool
    {
        return in_array($this->status, ['pending', 'scheduled', 'in_progress']);
    }
    
    public function getCanStartAttribute(): bool
    {
        return $this->status === 'scheduled' || $this->status === 'pending';
    }
    
    // Методы для работы с консультацией
    public function startConsultation(int $expertId): void
    {
        $this->update([
            'expert_id' => $expertId,
            'status' => 'in_progress',
            'scheduled_at' => now(),
        ]);
    }
    
    public function completeConsultation(array $data = []): void
    {
        $this->update(array_merge([
            'status' => 'completed',
            'completed_at' => now(),
        ], $data));
    }
    
    public function addExpertAnalysis(string $analysis, array $recommendations = []): void
    {
        $this->update([
            'expert_analysis' => $analysis,
            'recommendations' => $recommendations,
        ]);
    }
    
    public function addQuestionAnswer(string $question, string $answer): void
    {
        $questions = $this->questions_asked ?? [];
        $answers = $this->answers_provided ?? [];
        
        $questions[] = $question;
        $answers[] = $answer;
        
        $this->update([
            'questions_asked' => $questions,
            'answers_provided' => $answers,
        ]);
    }
    
    public function requestAdditionalData(array $dataTypes): void
    {
        $this->update([
            'additional_data_requested' => $dataTypes,
        ]);
    }
    
    public function addCustomerFeedback(string $feedback, int $rating): void
    {
        $this->update([
            'customer_feedback' => $feedback,
            'rating' => $rating,
            'feedback_at' => now(),
        ]);
    }
    
    // Scope для фильтрации
    public function scopeForExpert($query, $expertId)
    {
        return $query->where('expert_id', $expertId)
                    ->orWhere(function($q) use ($expertId) {
                        $q->whereNull('expert_id')
                          ->where('status', 'scheduled');
                    });
    }
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }
    
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
    
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }
}