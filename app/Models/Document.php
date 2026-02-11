<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class Document extends Model
{
    //use Searchable;
    
    protected $fillable = [
        'car_model_id',
        'category_id', 
        'title',
        'content_text',
        'keywords',
        'original_filename',
        'file_type',
        'file_path',
        'source_url',
        'uploaded_by',
        'status',
        'embedding',
        'search_indexed',
        'is_parsed',
        'parsing_quality',
        'detected_section',
        'detected_system',
        'detected_component',
        'search_count',
        'view_count',
        'average_relevance',
        'search_vector',
        'keywords_text',
    ];
    
    protected $casts = [
        'search_indexed' => 'boolean',
        'is_parsed' => 'boolean',
        'parsing_quality' => 'float',
        'search_count' => 'integer',
        'view_count' => 'integer',
        'average_relevance' => 'float',
        'embedding' => 'array',
    ];
    

    // Статусы документа
    const STATUS_UPLOADED = 'uploaded';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PARSED = 'parsed';
    const STATUS_INDEXED = 'indexed';
    const STATUS_PROCESSED = 'processed';
    const STATUS_PARSE_ERROR = 'parse_error';
    const STATUS_INDEX_ERROR = 'index_error';
    
    // === ДОБАВЛЕННЫЕ МЕТОДЫ ДЛЯ ПОИСКА ===
    
    /**
     * Scope для поиска документов
     */
    public function scopeSearch(Builder $query, string $searchTerm, array $filters = [], int $limit = null, int $offset = 0)
{
    $searchTerm = $this->prepareSearchTerm($searchTerm);
    
    return $query->where(function($q) use ($searchTerm) {
            // Проверяем наличие FULLTEXT индекса
            if ($this->hasFulltextIndex()) {
                // Используем существующие поля из вашей таблицы
                $q->whereRaw("MATCH(title, content_text, detected_system, detected_component) AGAINST(? IN BOOLEAN MODE)", [$searchTerm]);
            } else {
                // Fallback на LIKE поиск по существующим полям
                $likeTerm = '%' . str_replace(' ', '%', $searchTerm) . '%';
                $q->where(function($subQ) use ($likeTerm) {
                    $subQ->where('title', 'LIKE', $likeTerm)
                         ->orWhere('content_text', 'LIKE', $likeTerm);
                    
                    // Только если поля существуют и не NULL
                    if ($this->detected_system) {
                        $subQ->orWhere('detected_system', 'LIKE', $likeTerm);
                    }
                    
                    if ($this->detected_component) {
                        $subQ->orWhere('detected_component', 'LIKE', $likeTerm);
                    }
                    
                    // Поиск по keywords (JSON массиву)
                    // Преобразуем keywords в текст для поиска
                    $subQ->orWhere(function($keywordsQuery) use ($likeTerm) {
                        $keywordsQuery->whereNotNull('keywords')
                            ->where('keywords', 'LIKE', '%"' . str_replace('%', '', $likeTerm) . '"%');
                    });
                });
            }
        })
        ->when(!empty($filters), function($q) use ($filters) {
            foreach ($filters as $key => $value) {
                if ($value !== null && $value !== '') {
                    if (in_array($key, ['car_model_id', 'category_id', 'file_type', 'detected_system', 'detected_component'])) {
                        $q->where($key, $value);
                    }
                }
            }
        })
        ->where('is_parsed', true)
        ->where('status', self::STATUS_PROCESSED)
        ->orderBy('average_relevance', 'desc')
        ->orderBy('search_count', 'desc')
        ->orderBy('created_at', 'desc')
        ->when($limit, function($q) use ($limit, $offset) {
            $q->skip($offset)->take($limit);
        });
}


/**
 * Получить keywords как текст (accessor)
 */
public function getKeywordsTextAttribute(): string
{
    if (empty($this->keywords)) {
        return '';
    }
    
    if (is_array($this->keywords)) {
        return implode(', ', $this->keywords);
    }
    
    if (is_string($this->keywords)) {
        $keywords = json_decode($this->keywords, true);
        if (is_array($keywords)) {
            return implode(', ', $keywords);
        }
        return $this->keywords;
    }
    
    return '';
}


/**
 * Преобразование keywords для поиска
 */
private function keywordsToSearchText($keywords): string
{
    if (empty($keywords)) {
        return '';
    }
    
    if (is_array($keywords)) {
        return implode(' ', $keywords);
    }
    
    if (is_string($keywords)) {
        $keywordsArray = json_decode($keywords, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($keywordsArray)) {
            return implode(' ', $keywordsArray);
        }
        return $keywords;
    }
    
    return '';
}
    
    /**
     * Метод для автодополнения
     */
    public function scopeAutocomplete(Builder $query, string $searchTerm): array
    {
        if (strlen($searchTerm) < 2) {
            return [];
        }
        
        $likeTerm = '%' . $searchTerm . '%';
        
        // Поиск по заголовкам
        $titles = $query->where('title', 'LIKE', $likeTerm)
            ->where('is_parsed', true)
            ->where('status', self::STATUS_PROCESSED)
            ->orderBy('search_count', 'desc')
            ->limit(5)
            ->pluck('title')
            ->toArray();
        
        // Поиск по системам
        $systems = $query->where('detected_system', 'LIKE', $likeTerm)
            ->where('detected_system', '!=', '')
            ->where('is_parsed', true)
            ->where('status', self::STATUS_PROCESSED)
            ->distinct()
            ->limit(5)
            ->pluck('detected_system')
            ->toArray();
        
        // Поиск по компонентам
        $components = $query->where('detected_component', 'LIKE', $likeTerm)
            ->where('detected_component', '!=', '')
            ->where('is_parsed', true)
            ->where('status', self::STATUS_PROCESSED)
            ->distinct()
            ->limit(5)
            ->pluck('detected_component')
            ->toArray();
        
        // Поиск по ключевым словам из keywords_text
        $keywords = [];
        if (!empty($this->keywords_text)) {
            $keywordArray = explode(',', $this->keywords_text);
            foreach ($keywordArray as $keyword) {
                if (stripos($keyword, $searchTerm) !== false) {
                    $keywords[] = trim($keyword);
                }
            }
            $keywords = array_slice(array_unique($keywords), 0, 5);
        }
        
        // Объединяем все результаты
        $results = array_unique(array_merge($titles, $systems, $components, $keywords));
        
        return array_slice($results, 0, 10);
    }
    
    /**
     * Подготовка поискового термина
     */
    private function prepareSearchTerm(string $term): string
    {
        $term = mb_strtolower($term);
        $term = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $term);
        $term = preg_replace('/\s+/', ' ', $term);
        
        // Для FULLTEXT поиска преобразуем в форму с *
        if ($this->hasFulltextIndex()) {
            $words = explode(' ', $term);
            $words = array_filter($words, function($word) {
                return mb_strlen($word) > 2;
            });
            $words = array_map(function($word) {
                return '+' . $word . '*';
            }, $words);
            
            return implode(' ', $words);
        }
        
        return trim($term);
    }
    
    /**
     * Проверка наличия FULLTEXT индекса
     */
    private function hasFulltextIndex(): bool
    {
        static $hasIndex = null;
        
        if ($hasIndex === null) {
            try {
                $result = \DB::select("SHOW INDEX FROM documents WHERE Index_type = 'FULLTEXT'");
                $hasIndex = !empty($result);
            } catch (\Exception $e) {
                $hasIndex = false;
            }
        }
        
        return $hasIndex;
    }
    
    /**
     * Увеличить счетчик поиска
     */
    public function incrementSearchCount()
    {
        $this->increment('search_count');
    }
    
    /**
     * Увеличить счетчик просмотров
     */
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }
    
    // === КОНЕЦ ДОБАВЛЕННЫХ МЕТОДОВ ===
    
    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray()
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];
        $sections = is_array($this->sections) ? $this->sections : [];
        
        $array = [
            'id' => $this->id,
            'title' => $this->title,
            'content_text' => $this->content_text,
            'keywords' => $this->keywords ?? [],
            'car_model_id' => $this->car_model_id,
            'category_id' => $this->category_id,
            'brand_id' => $this->carModel->brand_id ?? null,
            'car_model' => $this->carModel ? ($this->carModel->brand->name . ' ' . $this->carModel->name) : null,
            'category' => $this->category->name ?? null,
            'brand' => $this->carModel->brand->name ?? null,
            'file_type' => $this->file_type,
            'status' => $this->status,
            'created_at' => $this->created_at->timestamp,
            'word_count' => $this->word_count ?? 0,
            'has_images' => $this->has_images ?? false,
            
            // Структурированные данные
            'document_type' => $metadata['document_type'] ?? 'unknown',
            'car_parts' => $metadata['car_parts'] ?? [],
            'procedures' => $metadata['procedures'] ?? [],
            'warnings' => $metadata['warnings'] ?? [],
            'tools_required' => $metadata['tools_required'] ?? [],
            'estimated_time' => $metadata['estimated_time'] ?? [],
            'difficulty' => $metadata['difficulty'] ?? 'medium',
            'years' => $metadata['car_specific']['years'] ?? [],
            'engine_codes' => $metadata['car_specific']['engine_codes'] ?? [],
        ];
        
        // Добавляем поисковые поля только если они существуют
        if (!empty($this->content_text)) {
            $array['search_terms'] = $this->extractSearchTerms($this->content_text);
            $array['technical_terms'] = $this->extractTechnicalTerms($this->content_text);
        }
        
        if (!empty($sections)) {
            $array['sections_titles'] = array_column($sections, 'title');
            $sectionContents = array_filter(array_column($sections, 'content'));
            if (!empty($sectionContents)) {
                $array['section_contents'] = $sectionContents;
            }
        }
        
        return $array;
    }
    
    /**
     * Извлекает поисковые термины
     */
    private function extractSearchTerms(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{Cyrillic}\p{Latin}\s]/u', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        $stopWords = ['и', 'в', 'на', 'с', 'по', 'для', 'из', 'от', 'до', 'за', 'к', 'у', 'о', 'об', 'не'];
        $words = array_filter($words, function($word) use ($stopWords) {
            return mb_strlen($word) > 2 && !in_array($word, $stopWords);
        });
        
        return array_unique(array_values($words));
    }
    
    private function extractTechnicalTerms(string $text): array
    {
        $technicalTerms = [
            // Двигатель
            'двигатель', 'мотор', 'коленвал', 'распредвал', 'поршень', 'цилиндр', 'гбц',
            'клапан', 'топливо', 'бензин', 'дизель', 'инжектор', 'карбюратор', 'тнвд',
            'свеча', 'зажигание', 'масло', 'фильтр', 'воздушный', 'масляный',
            
            // Трансмиссия
            'коробка', 'акпп', 'мкпп', 'вариатор', 'сцепление', 'диск', 'муфта',
            'редуктор', 'дифференциал', 'раздатка', 'кардан', 'шрус', 'привод',
            
            // Ходовая
            'подвеска', 'амортизатор', 'стойка', 'пружина', 'рычаг', 'сайлентблок',
            'шаровая', 'ступица', 'подшипник', 'тормоз', 'колодка', 'диск', 'барабан',
            
            // Электрика
            'аккумулятор', 'генератор', 'стартер', 'реле', 'предохранитель', 'датчик',
            'проводка', 'разъем', 'лампа', 'фары', 'поворотник',
            
            // Действия
            'замена', 'ремонт', 'регулировка', 'настройка', 'диагностика', 'установка',
            'снятие', 'разборка', 'сборка', 'чистка', 'смазка',
            
            // Симптомы
            'стук', 'скрип', 'шум', 'вибрация', 'люфт', 'течь', 'протекает', 'горит',
            'не работает', 'не включается', 'глохнет', 'не заводится', 'дергается',
        ];
        
        $text = mb_strtolower($text);
        $foundTerms = [];
        
        foreach ($technicalTerms as $term) {
            if (str_contains($text, $term)) {
                $foundTerms[] = $term;
            }
        }
        
        return $foundTerms;
    }
    
    /**
     * Should the model be searchable?
     */
    public function shouldBeSearchable()
    {
        return $this->status === 'processed';
    }
    
    /**
     * Relationships
     */
    public function carModel()
    {
        return $this->belongsTo(CarModel::class);
    }
    
     public function category(): BelongsTo
    {
        return $this->belongsTo(RepairCategory::class, 'category_id');
    }
    
    // Добавьте этот метод для связи uploaded_by
    public function uploadedByUser()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
    
    // Альтернативное имя для той же связи
    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function ngrams()
    {
        return $this->hasMany(DocumentNgram::class);
    }
    
    /**
     * Scope для документов, готовых к парсингу
     */
    public function scopeReadyForParsing($query)
    {
        return $query->whereIn('status', ['uploaded', 'processing'])
                    ->where('is_parsed', false)
                    ->whereNotNull('file_path');
    }
    
    /**
     * Scope для документов, готовых к индексации
     */
    public function scopeReadyForIndexing($query)
    {
        return $query->where('is_parsed', true)
                    ->where('search_indexed', false)
                    ->whereNotNull('content_text')
                    ->where('status', '!=', 'indexed');
    }
    
    /**
     * Получить статус в читаемом формате
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'uploaded' => 'Загружен',
            'processing' => 'В обработке',
            'parsed' => 'Распарсен',
            'indexed' => 'Проиндексирован',
            'processed' => 'Обработан',
            'parse_error' => 'Ошибка парсинга',
            'index_error' => 'Ошибка индексации',
        ];
        
        return $labels[$this->status] ?? $this->status;
    }

    public function getContentAttribute($value)
    {
        // Убедитесь, что возвращаем строку
        return $value ?? '';
    }

    public function getContentTextAttribute($value)
    {
        return $value ?? '';
    }
    
    // Accessor для keywords (если его еще нет)
    public function getKeywordsAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        
        return [];
    }

    /**
 * Связь с таблицей страниц документа
 */
public function pages()
{
    return $this->hasMany(DocumentPage::class, 'document_id', 'id');
}

/**
 * Связь с конкретной страницей по номеру
 */
public function page($number)
{
    return $this->hasOne(DocumentPage::class, 'document_id', 'id')
        ->where('page_number', $number);
}

/**
 * Связь со скриншотами документа
 */
public function screenshots()
{
    return $this->hasMany(DocumentScreenshot::class, 'document_id', 'id');
}

/**
 * Связь с изображениями документа
 */
public function images()
{
    return $this->hasMany(DocumentImage::class, 'document_id', 'id');
}
}