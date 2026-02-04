<?php
// app/Console/Commands/DiagnoseSearch.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Document;

class DiagnoseSearch extends Command
{
    protected $signature = 'search:diagnose {query?}';
    protected $description = 'Диагностика поисковой системы';

    public function handle()
    {
        $query = $this->argument('query') ?? 'двигатель';
        
        $this->info("=== Диагностика поиска для запроса: '{$query}' ===");
        
        // 1. Проверка структуры
        $this->info("\n1. Проверка структуры таблицы:");
        $requiredFields = ['title', 'content_text', 'detected_system', 'detected_component'];
        foreach ($requiredFields as $field) {
            if (Schema::hasColumn('documents', $field)) {
                $this->info("  ✓ {$field}");
            } else {
                $this->error("  ✗ {$field} - отсутствует!");
            }
        }
        
        // 2. Проверка индексов
        $this->info("\n2. Проверка индексов:");
        try {
            $indexes = DB::select("SHOW INDEX FROM documents");
            $hasFulltext = false;
            foreach ($indexes as $index) {
                $this->line("  - {$index->Key_name} ({$index->Index_type}) на {$index->Column_name}");
                if ($index->Index_type == 'FULLTEXT') {
                    $hasFulltext = true;
                }
            }
            
            if ($hasFulltext) {
                $this->info("  ✓ FULLTEXT индекс найден");
            } else {
                $this->warn("  ⚠ FULLTEXT индекс не найден");
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Ошибка проверки индексов: " . $e->getMessage());
        }
        
        // 3. Проверка данных
        $this->info("\n3. Статистика документов:");
        $total = Document::count();
        $parsed = Document::where('is_parsed', true)->count();
        $processed = Document::where('status', 'processed')->count();
        $withContent = Document::whereNotNull('content_text')->where('content_text', '!=', '')->count();
        
        $this->info("  Всего документов: {$total}");
        $this->info("  Распарсено (is_parsed): {$parsed}");
        $this->info("  Обработано (status=processed): {$processed}");
        $this->info("  С контентом: {$withContent}");
        
        // 4. Тестовые запросы
        $this->info("\n4. Тестовые поисковые запросы:");
        
        // SQL FULLTEXT
        try {
            $sql = "SELECT COUNT(*) as count FROM documents 
                    WHERE MATCH(title, content_text, detected_system, detected_component) 
                    AGAINST(? IN BOOLEAN MODE)
                    AND is_parsed = 1 
                    AND status = 'processed'";
            
            $result = DB::select($sql, [$query . '*'])[0]->count;
            $this->info("  FULLTEXT SQL: {$result} результатов");
        } catch (\Exception $e) {
            $this->error("  FULLTEXT SQL ошибка: " . $e->getMessage());
        }
        
        // SQL LIKE
        $likeTerm = '%' . $query . '%';
        $likeCount = DB::select("
            SELECT COUNT(*) as count FROM documents 
            WHERE (title LIKE ? OR content_text LIKE ? OR detected_system LIKE ? OR detected_component LIKE ?)
            AND is_parsed = 1 
            AND status = 'processed'
        ", [$likeTerm, $likeTerm, $likeTerm, $likeTerm])[0]->count;
        
        $this->info("  LIKE SQL: {$likeCount} результатов");
        
        // Eloquent
        $eloquentCount = Document::where(function($q) use ($likeTerm) {
                $q->where('title', 'LIKE', $likeTerm)
                  ->orWhere('content_text', 'LIKE', $likeTerm)
                  ->orWhere('detected_system', 'LIKE', $likeTerm)
                  ->orWhere('detected_component', 'LIKE', $likeTerm);
            })
            ->where('is_parsed', true)
            ->where('status', 'processed')
            ->count();
        
        $this->info("  Eloquent: {$eloquentCount} результатов");
        
        // 5. Примеры документов
        $this->info("\n5. Примеры обработанных документов:");
        $sampleDocs = Document::where('is_parsed', true)
            ->where('status', 'processed')
            ->whereNotNull('content_text')
            ->limit(3)
            ->get();
            
        if ($sampleDocs->count() > 0) {
            foreach ($sampleDocs as $doc) {
                $this->line("  ID {$doc->id}: {$doc->title}");
                $this->line("    Система: {$doc->detected_system}, Компонент: {$doc->detected_component}");
                $this->line("    Контент: " . substr($doc->content_text ?? '', 0, 100) . "...");
                $this->line("");
            }
        } else {
            $this->warn("  Нет подходящих документов для поиска!");
            
            // Покажем какие документы вообще есть
            $this->info("  Существующие документы:");
            $allDocs = Document::limit(5)->get();
            foreach ($allDocs as $doc) {
                $this->line("    ID {$doc->id}: {$doc->title} [статус: {$doc->status}, парсинг: {$doc->is_parsed}]");
            }
        }
        
        return 0;
    }
}