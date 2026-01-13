<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairCategory extends Model
{
    use HasFactory;
    protected $table = 'repair_categories'; 
    protected $fillable = ['name', 'description', 'parent_id'];

    public function parent()
    {
        return $this->belongsTo(RepairCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(RepairCategory::class, 'parent_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'category_id');
    }
}