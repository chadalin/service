<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckDatabase extends Command
{
    protected $signature = 'db:check';
    protected $description = 'Проверка структуры базы данных';
    
    public function handle()
    {
        $this->info('Проверка структуры базы данных...');
        
        // Проверяем таблицу documents
        $this->checkTable('documents', [
            'id', 'car_model_id', 'category_id', 'title', 'content_text',
            'keywords', 'original_filename', 'file_type', 'file_path',
            'source_url', 'uploaded_by', 'status', 'created_at', 'updated_at'
        ]);
        
        // Проверяем таблицу car_models
        $this->checkTable('car_models', [
            'id', 'brand_id', 'name', 'name_cyrillic', 'year_from', 'year_to'
        ]);
        
        // Проверяем таблицу brands
        $this->checkTable('brands', ['id', 'name']);
        
        // Проверяем таблицу search_queries
        $this->checkTable('search_queries', [
            'id', 'user_id', 'query_text', 'car_model_id', 'results_count'
        ]);
        
        // Статистика документов
        $this->showDocumentStats();
        
        return 0;
    }
    
    private function checkTable($tableName, $expectedColumns)
    {
        $this->info("\nТаблица: {$tableName}");
        
        if (!Schema::hasTable($tableName)) {
            $this->error("  ❌ Таблица не существует!");
            return;
        }
        
        $existingColumns = Schema::getColumnListing($tableName);
        
        // Проверяем наличие обязательных колонок
        foreach ($expectedColumns as $column) {
            if (in_array($column, $existingColumns)) {
                $this->line("  ✓ {$column}");
            } else {
                $this->error("  ✗ {$column} - отсутствует");
            }
        }
        
        // Дополнительные колонки
        $extraColumns = array_diff($existingColumns, $expectedColumns);
        if (!empty($extraColumns)) {
            $this->info("  Дополнительные колонки:");
            foreach ($extraColumns as $column) {
                $this->line("    • {$column}");
            }
        }
    }
    
    private function showDocumentStats()
    {
        $this->info("\n📊 Статистика документов:");
        
        try {
            // Общее количество
            $total = DB::table('documents')->count();
            $this->info("  Всего документов: {$total}");
            
            if ($total > 0) {
                // По статусам
                $statuses = DB::table('documents')
                    ->select('status', DB::raw('COUNT(*) as count'))
                    ->groupBy('status')
                    ->get();
                
                $this->info("  По статусам:");
                foreach ($statuses as $status) {
                    $this->line("    • {$status->status}: {$status->count}");
                }
                
                // По типам файлов
                $fileTypes = DB::table('documents')
                    ->select('file_type', DB::raw('COUNT(*) as count'))
                    ->whereNotNull('file_type')
                    ->groupBy('file_type')
                    ->orderBy('count', 'desc')
                    ->limit(5)
                    ->get();
                
                if ($fileTypes->isNotEmpty()) {
                    $this->info("  По типам файлов:");
                    foreach ($fileTypes as $type) {
                        $this->line("    • {$type->file_type}: {$type->count}");
                    }
                }
                
                // Последние документы
                $recent = DB::table('documents')
                    ->select('id', 'title', 'status', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->limit(3)
                    ->get();
                
                if ($recent->isNotEmpty()) {
                    $this->info("  Последние документы:");
                    foreach ($recent as $doc) {
                        $date = date('d.m.Y', strtotime($doc->created_at));
                        $this->line("    • #{$doc->id}: {$doc->title} ({$doc->status}, {$date})");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("  Ошибка получения статистики: " . $e->getMessage());
        }
    }
}