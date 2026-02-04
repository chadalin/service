<?php
// app/Console/Commands/CreateFulltextIndex.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateFulltextIndex extends Command
{
    protected $signature = 'search:create-fulltext-index';
    protected $description = 'Создать FULLTEXT индекс для таблицы documents';

    public function handle()
    {
        $this->info('Проверка наличия FULLTEXT индексов...');
        
        try {
            // Проверяем, есть ли уже индекс
            $indexes = DB::select("SHOW INDEX FROM documents WHERE Index_type = 'FULLTEXT'");
            
            if (!empty($indexes)) {
                $this->warn('FULLTEXT индексы уже существуют.');
                $this->table(['Key_name', 'Column_name'], array_map(function($index) {
                    return [
                        'Key_name' => $index->Key_name,
                        'Column_name' => $index->Column_name
                    ];
                }, $indexes));
                return 0;
            }
            
            $this->info('Создание FULLTEXT индекса...');
            
            // Создаем FULLTEXT индекс
            DB::statement("
                ALTER TABLE documents 
                ADD FULLTEXT INDEX documents_fulltext_idx (title, content_text, keywords_text, detected_system, detected_component)
            ");
            
            $this->info('✅ FULLTEXT индекс успешно создан!');
            
            // Обновляем все документы как проиндексированные
            DB::table('documents')->update(['search_indexed' => true]);
            $this->info('✅ Документы помечены как проиндексированные');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Ошибка при создании FULLTEXT индекса: ' . $e->getMessage());
            $this->error('Убедитесь что используется MyISAM или InnoDB с поддержкой FULLTEXT');
            
            // Показываем рекомендации
            $this->line('');
            $this->info('Рекомендации:');
            $this->line('1. Для InnoDB используйте MySQL 5.6+');
            $this->line('2. Для поддержки FULLTEXT в InnoDB нужно:');
            $this->line('   - MySQL 5.6.4+ для InnoDB FULLTEXT');
            $this->line('   - innodb_ft_min_token_size = 3 в my.cnf');
            
            return 1;
        }
    }
}