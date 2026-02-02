<?php
// app/Console/Commands/CheckDocuments.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;

class CheckDocuments extends Command
{
    protected $signature = 'documents:check';
    protected $description = 'Проверить документы и их содержимое';

    public function handle()
    {
        $total = Document::count();
        $parsed = Document::where('is_parsed', true)->count();
        $withContent = Document::whereNotNull('content_text')->count();
        
        $this->info("Всего документов: {$total}");
        $this->info("Распознанных документов: {$parsed}");
        $this->info("Документов с текстом: {$withContent}");
        
        // Показать примеры документов
        $this->info("\nПримеры документов:");
        $documents = Document::whereNotNull('content_text')
            ->limit(5)
            ->get(['id', 'title', 'file_type', 'word_count']);
        
        foreach ($documents as $doc) {
            $this->line("ID: {$doc->id} | {$doc->title} | {$doc->file_type} | {$doc->word_count} слов");
        }
        
        // Проверить первые 100 символов текста
        if ($documents->count() > 0) {
            $firstDoc = $documents->first();
            $sample = substr($firstDoc->content_text ?? '', 0, 200);
            $this->info("\nПример текста (первые 200 символов):");
            $this->line($sample . '...');
        }
    }
}