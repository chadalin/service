<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentNgram extends Model
{
    protected $table = 'document_ngrams';
    
    protected $fillable = [
        'document_id',
        'ngram',
        'ngram_type',
        'position',
        'frequency'
    ];
    
    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}