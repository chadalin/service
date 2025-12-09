<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_id',
        'brand_id', 
        'name',
        'name_cyrillic',
        'class',
        'year_from',
        'year_to',
        'years', 
        'engine_types'
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function searchQueries()
    {
        return $this->hasMany(SearchQuery::class);
    }

    public function getFullNameAttribute()
    {
        return $this->brand->name . ' ' . $this->name;
    }

    public function getFullNameCyrillicAttribute()
    {
        return $this->brand->name_cyrillic . ' ' . $this->name_cyrillic;
    }
}