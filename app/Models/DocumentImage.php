<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentImage extends Model
{
   protected $fillable = [
        'document_id',
        'page_id',
        'page_number',
        'filename',
        'path',
        'url',
        'thumbnail_path',
        'thumbnail_url',
        'width',
        'height',
        'original_width',
        'original_height',
        'size',
        'thumbnail_size',
        'description',
        'position',
        'mime_type',
        'extension',
        'is_preview',
        'status'
    ];
    
    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'original_width' => 'integer',
        'original_height' => 'integer',
        'size' => 'integer',
        'thumbnail_size' => 'integer',
        'position' => 'integer',
        'is_preview' => 'boolean'
    ];
    
    /**
     * Отношение к документу
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
    
    /**
     * Отношение к странице
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(DocumentPage::class, 'page_id');
    }
    
    /**
     * Получить полный URL изображения
     */
    public function getFullUrlAttribute()
    {
        return Storage::url($this->path);
    }
    
    /**
     * Получить путь для превью изображения
     */
    public function getThumbnailUrlAttribute()
    {
        $pathinfo = pathinfo($this->path);
        $thumbnailPath = $pathinfo['dirname'] . '/thumbs/' . $pathinfo['filename'] . '_thumb.' . $pathinfo['extension'];
        
        if (Storage::exists($thumbnailPath)) {
            return Storage::url($thumbnailPath);
        }
        
        return $this->full_url;
    }
    
    /**
     * Получить информацию об изображении
     */
    public function getInfoAttribute()
    {
        return [
            'filename' => $this->filename,
            'dimensions' => "{$this->width}x{$this->height}",
            'size' => $this->formatSize($this->size),
            'description' => $this->description,
            'position' => $this->position
        ];
    }
    
    /**
     * Форматирование размера файла
     */
    private function formatSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

     /**
     * Получить размер миниатюры в читаемом формате
     */
    public function getFormattedThumbnailSizeAttribute()
    {
        $bytes = $this->thumbnail_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
    
    /**
     * Получить соотношение сторон
     */
    public function getAspectRatioAttribute()
    {
        if ($this->height > 0) {
            return round($this->width / $this->height, 2);
        }
        return 0;
    }
}