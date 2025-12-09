<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name', 
        'name_cyrillic',
        'is_popular',
        'country', 
        'year_from',
        'year_to',
        'logo', 
        'description'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function carModels()
    {
        return $this->hasMany(CarModel::class, 'brand_id');
    }

    public function documents()
    {
        return $this->hasManyThrough(Document::class, CarModel::class, 'brand_id', 'car_model_id');
    }
}