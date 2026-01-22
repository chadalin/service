<?php

namespace App\Models\Diagnostic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Symptom extends Model
{
    protected $table = 'diagnostic_symptoms';
    
    protected $fillable = [
        'name', 'slug', 'description', 'related_systems',
        'image', 'frequency', 'is_active'
    ];
    
    protected $casts = [
        'related_systems' => 'array',
        'is_active' => 'boolean',
    ];
    
    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class, 'symptom_id');
    }
    
    public function incrementFrequency(): void
    {
        $this->increment('frequency');
    }
    
    // Скоуп для популярных симптомов
    public function scopePopular($query, $limit = 10)
    {
        return $query->where('is_active', true)
                    ->orderBy('frequency', 'desc')
                    ->limit($limit);
    }
}