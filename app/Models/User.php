<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'pin_code',
        'pin_expires_at',
        'company_name',
        'role',
        'status'
    ];

    protected $hidden = [
        'password',
        'pin_code',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'pin_expires_at' => 'datetime',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function searchQueries()
    {
        return $this->hasMany(SearchQuery::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isPinValid()
    {
        return $this->pin_expires_at && $this->pin_expires_at->isFuture();
    }


     // Добавьте эти методы для работы с консультациями
    public function consultations(): HasMany
    {
        return $this->hasMany(\App\Models\Diagnostic\Consultation::class, 'user_id');
    }
    
    public function expertConsultations(): HasMany
    {
        return $this->hasMany(\App\Models\Diagnostic\Consultation::class, 'expert_id');
    }
    
    public function getUnreadConsultationMessagesCountAttribute(): int
    {
        return \App\Models\Diagnostic\Consultation::where('user_id', $this->id)
            ->orWhere('expert_id', $this->id)
            ->whereHas('messages', function($query) {
                $query->where('user_id', '!=', $this->id)
                      ->whereNull('read_at');
            })
            ->count();
    }
    
    public function getPendingConsultationsCountAttribute(): int
    {
        if (!$this->is_expert && !$this->is_admin) {
            return 0;
        }
        
        return \App\Models\Diagnostic\Consultation::whereNull('expert_id')
            ->where('status', 'pending')
            ->count();
    }
    
    public function getIsAdminAttribute(): bool
    {
        return $this->role === 'admin';
    }
}