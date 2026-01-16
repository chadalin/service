<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'content',
        'summary',
        'chunks',
        'char_count',
        'word_count',
    ];

    protected $casts = [
        'chunks' => 'array',
    ];
    
    public function document()
    {
        return $this->belongsTo(Document::class);
    }
    
    // Метод для разбивки текста на части (для поиска)
    public function chunkContent($chunkSize = 10000)
    {
        $content = $this->content;
        if (!$content) return [];
        
        $chunks = [];
        $length = mb_strlen($content, 'UTF-8');
        
        for ($i = 0; $i < $length; $i += $chunkSize) {
            $chunk = mb_substr($content, $i, $chunkSize, 'UTF-8');
            $chunks[] = [
                'text' => $chunk,
                'start' => $i,
                'end' => min($i + $chunkSize, $length),
                'hash' => md5($chunk),
            ];
        }
        
        $this->update(['chunks' => $chunks]);
        return $chunks;
    }
    
    // Метод для получения части текста
    public function getChunk($index = 0)
    {
        $chunks = $this->chunks ?? [];
        return $chunks[$index]['text'] ?? null;
    }
}