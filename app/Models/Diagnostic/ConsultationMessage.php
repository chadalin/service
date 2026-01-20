<?php

namespace App\Models\Diagnostic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationMessage extends Model
{
    protected $table = 'consultation_messages';
    
    protected $fillable = [
        'consultation_id',
        'user_id',
        'message',
        'type',
        'metadata',
        'read_at',
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
    ];
    
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
    
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }
    
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }
    
    public function getAttachmentsAttribute(): array
    {
        return $this->metadata['attachments'] ?? [];
    }
}