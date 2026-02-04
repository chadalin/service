<?php
// app/Console/Commands/CreateDocumentFulltextIndex.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDocumentFulltextIndex extends Command
{
    protected $signature = 'documents:create-fulltext-index';
    protected $description = 'Создать FULLTEXT индекс для существующих полей documents';

    public function handle()
    {
        $this->info('Проверка структуры таблицы documents...');
        
        $fields = ['title', 'content_text', 'detected_system', 'detected_component'];
        $existingFields = [];
        
        foreach ($fields as $field) {
            if (Schema::hasColumn('documents', $field)) {
                $existingFields[] = $field;
                $this->info("✓ Поле {$field} существует");
            } else {
                $this->warn("✗ Поле {$field} отсутствует");
            }
        }
        
        if (count($existingFields) < 2) {
            $this->error('Недостаточно полей для создания FULLTEXT индекса');
            return 1;
        }
        
        try {
            // Проверяем существующие индексы правильно
            $indexes = DB::select("
                SELECT INDEX_NAME, COLUMN_NAME, INDEX_TYPE 
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'documents' 
                AND INDEX_TYPE = 'FULLTEXT'
            ");
            
            if (!empty($indexes)) {
                $this->warn('FULLTEXT индексы уже существуют:');
                foreach ($indexes as $index) {
                    $this->line("  - {$index->INDEX_NAME} на поле {$index->COLUMN_NAME}");
                }
                
                if ($this->confirm('Удалить существующие индексы и создать новый?')) {
                    // Удаляем все существующие FULLTEXT индексы
                    foreach ($indexes as $index) {
                        try {
                            DB::statement("ALTER TABLE documents DROP INDEX `{$index->INDEX_NAME}`");
                            $this->info("Индекс {$index->INDEX_NAME} удален");
                        } catch (\Exception $e) {
                            $this->warn("Не удалось удалить индекс {$index->INDEX_NAME}: " . $e->getMessage());
                        }
                    }
                } else {
                    return 0;
                }
            }
            
            $this->info('Создание FULLTEXT индекса...');
            
            // Создаем индекс только на существующие поля
            $fieldsString = implode(', ', $existingFields);
            $indexName = 'documents_fulltext_idx_' . time();
            
            DB::statement("ALTER TABLE documents ADD FULLTEXT INDEX `{$indexName}` ({$fieldsString})");
            
            $this->info('✅ FULLTEXT индекс успешно создан!');
            $this->info("Имя индекса: {$indexName}");
            $this->info('Используемые поля: ' . $fieldsString);
            
            // Обновляем статус документов
            $updated = DB::table('documents')
                ->where('is_parsed', true)
                ->update(['search_indexed' => true]);
                
            $this->info("✅ {$updated} документов помечены как проиндексированные");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Ошибка при создании FULLTEXT индекса: ' . $e->getMessage());
            
            // Альтернативный вариант - использовать обычные индексы
            $this->info("\nСоздаю обычные индексы вместо FULLTEXT...");
            try {
                foreach ($existingFields as $field) {
                    DB::statement("ALTER TABLE documents ADD INDEX `idx_{$field}` (`{$field}`)");
                    $this->info("Создан индекс idx_{$field}");
                }
                $this->info('✅ Обычные индексы созданы успешно');
                return 0;
            } catch (\Exception $e2) {
                $this->error('Ошибка создания обычных индексов: ' . $e2->getMessage());
                return 1;
            }
        }
    }
}