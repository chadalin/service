<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}