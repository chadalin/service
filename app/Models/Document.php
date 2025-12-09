<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_model_id',
        'category_id',
        'title',
        'content_text',
        'original_filename',
        'file_type',
        'file_path',
        'source_url',
        'uploaded_by',
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function carModel()
    {
        return $this->belongsTo(CarModel::class);
    }

    public function category()
    {
        return $this->belongsTo(RepairCategory::class, 'category_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}