<?php
// app/Models/SearchLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    protected $fillable = [
        'query',
        'filters',
        'results_count',
        'response_time',
        'user_id',
        'user_agent',
        'ip_address',
        'search_type',
        'search_meta',
        'clicked_result_id',
        'clicked_at'
    ];

    protected $casts = [
        'filters' => 'array',
        'search_meta' => 'array',
        'response_time' => 'float'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clickedDocument()
    {
        return $this->belongsTo(Document::class, 'clicked_result_id');
    }
}