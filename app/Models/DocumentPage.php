<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentPage extends Model
{
    protected $table = 'document_pages';
    
    protected $fillable = [
        'document_id',
        'page_number',
        'content',
        'content_text',
        'word_count',
        'character_count',
        'paragraph_count',
        'tables_count',
        'section_title',
        'metadata',
        'is_preview',
        'parsing_quality',
        'status'
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'is_preview' => 'boolean',
        'parsing_quality' => 'float',
        'word_count' => 'integer',
        'character_count' => 'integer',
        'paragraph_count' => 'integer',
        'tables_count' => 'integer'
    ];
    
    /**
     * Отношение к документу
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
    
    /**
     * Отношение к изображениям
     */
    public function images(): HasMany
    {
        return $this->hasMany(DocumentImage::class, 'page_id');
    }
    
    /**
     * Получить URL страницы
     */
    public function getUrlAttribute()
    {
        return route('document.page', [
            'document' => $this->document_id,
            'page' => $this->page_number
        ]);
    }
    
    /**
     * Получить следующий номер страницы
     */
    public function getNextPageAttribute()
    {
        return DocumentPage::where('document_id', $this->document_id)
            ->where('page_number', '>', $this->page_number)
            ->orderBy('page_number')
            ->first();
    }
    
    /**
     * Получить предыдущий номер страницы
     */
    public function getPreviousPageAttribute()
    {
        return DocumentPage::where('document_id', $this->document_id)
            ->where('page_number', '<', $this->page_number)
            ->orderBy('page_number', 'desc')
            ->first();
    }
    
    /**
     * Получить отформатированный контент
     */
    public function getFormattedContentAttribute()
    {
        return $this->content ?: nl2br(htmlspecialchars($this->content_text));
    }
    
    /**
     * Получить краткое содержание
     */
    public function getExcerptAttribute($length = 200)
    {
        $text = strip_tags($this->content_text);
        if (strlen($text) > $length) {
            return substr($text, 0, $length) . '...';
        }
        return $text;
    }
}