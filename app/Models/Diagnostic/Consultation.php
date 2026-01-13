<?php

namespace App\Models\Diagnostic;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consultation extends Model
{
    protected $table = 'diagnostic_consultations';
    
    protected $fillable = [
        'case_id', 'user_id', 'expert_id', 'type', 'price',
        'status', 'scheduled_at', 'duration', 'payment_id',
        'payment_status', 'paid_at', 'expert_notes', 'customer_feedback', 'rating'
    ];
    
    protected $casts = [
        'scheduled_at' => 'datetime',
        'paid_at' => 'datetime',
    ];
    
    public function case(): BelongsTo
    {
        return $this->belongsTo(Case::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function expert(): BelongsTo
    {
        return $this->belongsTo(User::class, 'expert_id');
    }
    
    public function report()
    {
        return $this->hasOne(Report::class);
    }
    
    // Проверить, оплачена ли консультация
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }
    
    // Запланировать консультацию
    public function schedule(\DateTimeInterface $dateTime, int $duration = 60): void
    {
        $this->update([
            'status' => 'scheduled',
            'scheduled_at' => $dateTime,
            'duration' => $duration,
        ]);
    }
    
    // Начать консультацию
    public function start(): void
    {
        $this->update(['status' => 'in_progress']);
    }
    
    // Завершить консультацию
    public function complete(string $expertNotes = null): void
    {
        $this->update([
            'status' => 'completed',
            'expert_notes' => $expertNotes,
        ]);
    }
}