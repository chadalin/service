<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PriceItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'price_items';
    
    protected $fillable = [
        'brand_id', // string(255)
        'catalog_brand', // string(100)
        'sku', // string(255)
        'name', // string
        'quantity', // integer
        'price', // decimal
        'unit', // string(50)
        'description', // text
        'compatibility', // json
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'compatibility' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Получить бренд (связь работает несмотря на разный тип)
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    /**
     * Получить связанные симптомы через совпадения
     */
    public function matchedSymptoms()
    {
        return $this->belongsToMany(
            Diagnostic\Symptom::class,
            'price_symptom_matches',
            'price_item_id',
            'symptom_id'
        )->withPivot('match_score', 'match_type')
         ->withTimestamps();
    }

    /**
     * Проверить, является ли SKU уникальным
     */
    public static function isSkuUnique(string $sku, ?int $excludeId = null): bool
    {
        $query = self::where('sku', $sku);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return !$query->exists();
    }

    /**
     * Найти запись по SKU
     */
    public static function findBySku(string $sku): ?self
    {
        return self::where('sku', $sku)->first();
    }

    /**
     * Мягкое совпадение с симптомами
     */
    public function findMatchingSymptoms(float $threshold = 0.3)
    {
        $symptoms = \App\Models\Diagnostic\Symptom::query()
            ->where('is_active', true)
            ->with('rules')
            ->get();

        $matches = [];
        
        foreach ($symptoms as $symptom) {
            $score = $this->calculateMatchScore($symptom);
            
            if ($score >= $threshold) {
                $matches[] = [
                    'symptom' => $symptom,
                    'score' => $score,
                    'type' => $this->determineMatchType($symptom, $score)
                ];
            }
        }
        
        // Сортируем по степени совпадения (по убыванию)
        usort($matches, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $matches;
    }

    /**
     * Рассчитать степень совпадения с симптомом
     */
    private function calculateMatchScore($symptom): float
    {
        $score = 0;
        
        // 1. Проверка по названию (основное совпадение)
        $nameScore = $this->calculateStringSimilarity(
            $this->name,
            $symptom->description ?? ''
        );
        $score += $nameScore * 0.6;
        
        // 2. Проверка по описанию симптома
        if ($symptom->description) {
            $descScore = $this->calculateStringSimilarity(
                $this->name,
                $symptom->description
            );
            $score += $descScore * 0.4;
        }
        
        // 3. Дополнительные проверки по SKU
        $skuWords = preg_split('/[_\-\s]+/', $this->sku);
        $symptomText = $symptom->name . ' ' . $symptom->description;
        
        foreach ($skuWords as $word) {
            if (strlen($word) > 3 && stripos($symptomText, $word) !== false) {
                $score += 0.1;
            }
        }
        
        return min($score, 1.0);
    }

    /**
     * Определить тип совпадения
     */
    private function determineMatchType($symptom, $score): string
    {
        if ($score >= 0.8) {
            return 'exact';
        } elseif ($score >= 0.6) {
            return 'strong';
        } elseif ($score >= 0.4) {
            return 'medium';
        } else {
            return 'weak';
        }
    }

    /**
     * Вычисление схожести строк
     */
    private function calculateStringSimilarity(string $str1, string $str2): float
    {
        $str1 = mb_strtolower(trim($str1));
        $str2 = mb_strtolower(trim($str2));
        
        if (empty($str1) || empty($str2)) {
            return 0;
        }
        
        // Простое сравнение слов
        $words1 = preg_split('/\s+/', $str1);
        $words2 = preg_split('/\s+/', $str2);
        
        $commonWords = array_intersect($words1, $words2);
        $totalWords = count(array_unique(array_merge($words1, $words2)));
        
        if ($totalWords === 0) {
            return 0;
        }
        
        $similarity = count($commonWords) / $totalWords;
        
        // Дополнительная проверка на вхождение подстрок
        if (stripos($str1, $str2) !== false || stripos($str2, $str1) !== false) {
            $similarity = max($similarity, 0.7);
        }
        
        return $similarity;
    }

    /**
     * Сохранить совпадения с симптомами
     */
    public function saveSymptomMatches(array $matches): void
    {
        $this->matchedSymptoms()->detach();
        
        foreach ($matches as $match) {
            $this->matchedSymptoms()->attach($match['symptom']->id, [
                'match_score' => $match['score'],
                'match_type' => $match['type']
            ]);
        }
    }

    /**
     * Получить диагностические симптомы, связанные через совпадения
     */
    public function getDiagnosticSymptomsAttribute()
    {
        return $this->matchedSymptoms()
            ->wherePivot('match_score', '>=', 0.3)
            ->orderByPivot('match_score', 'desc')
            ->get();
    }

    /**
     * Scope для поиска по бренду
     */
    public function scopeByBrand($query, $brandId)
    {
        return $query->where('brand_id', $brandId);
    }

    /**
     * Scope для поиска по каталожному бренду
     */
    public function scopeByCatalogBrand($query, $catalogBrand)
    {
        return $query->where('catalog_brand', 'like', "%{$catalogBrand}%");
    }

    /**
     * Scope для поиска по SKU или названию
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('sku', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
}