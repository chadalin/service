<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    protected $table = 'search_queries';
    
    protected $casts = [
        'query_analysis' => 'array',
        'filters' => 'array',
        'results_summary' => 'array',
        'execution_time' => 'float',
        'successful' => 'boolean'
    ];
    
    protected $fillable = [
        'query',
        'query_analysis',
        'result_count',
        'user_id',
        'user_ip',
        'user_agent',
        'execution_time',
        'successful',
        'filters',
        'results_summary',
        'car_model_id',
        'brand_id',
        'category_id',
        'search_type'
    ];
    
    // Для обратной совместимости
    public function getQueryTextAttribute()
    {
        return $this->query;
    }
    
    public function setQueryTextAttribute($value)
    {
        $this->attributes['query'] = $value;
    }
    
    public function getResultsCountAttribute()
    {
        return $this->result_count;
    }
    
    public function setResultsCountAttribute($value)
    {
        $this->attributes['result_count'] = $value;
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function carModel()
    {
        return $this->belongsTo(CarModel::class);
    }
    
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    
    public function category()
    {
        return $this->belongsTo(RepairCategory::class);
    }
    
    /**
     * Scope для успешных запросов
     */
    public function scopeSuccessful($query)
    {
        return $query->where('successful', true);
    }
    
    /**
     * Scope для запросов по типу поиска
     */
    public function scopeBySearchType($query, $type)
    {
        return $query->where('search_type', $type);
    }
    
    /**
     * Scope для популярных запросов
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->select('query', DB::raw('COUNT(*) as query_count'))
                    ->groupBy('query')
                    ->orderBy('query_count', 'desc')
                    ->limit($limit);
    }
    
    /**
     * Логирование поискового запроса
     */
    public static function logQuery($data)
    {
        try {
            return self::create([
                'query' => $data['query'] ?? $data['query_text'] ?? '',
                'query_analysis' => $data['query_analysis'] ?? null,
                'result_count' => $data['result_count'] ?? $data['results_count'] ?? 0,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'execution_time' => $data['execution_time'] ?? null,
                'successful' => $data['successful'] ?? true,
                'filters' => $data['filters'] ?? null,
                'results_summary' => $data['results_summary'] ?? null,
                'car_model_id' => $data['car_model_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'search_type' => $data['search_type'] ?? 'fulltext',
            ]);
        } catch (\Exception $e) {
            \Log::error('Ошибка логирования поискового запроса: ' . $e->getMessage());
            return null;
        }
    }
}