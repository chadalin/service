<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'query_text', 'car_model_id', 'results_count'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function carModel()
    {
        return $this->belongsTo(CarModel::class);
    }
}