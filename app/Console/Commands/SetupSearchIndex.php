<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use Illuminate\Support\Facades\DB;

class SetupSearchIndex extends Command
{
    protected $signature = 'search:setup';
    
    protected $description = 'Настройка и первичная индексация поиска';
    
    public function handle()
    {
        $this->info('Настройка поисковой системы...');
        
        // 1. Проверяем наличие колонок
        $this->checkDatabaseColumns();
        
        // 2. Обновляем статус документов
        $this->updateDocumentsStatus();
        
        // 3. Создаем начальные n-граммы
        $this->createInitialNgrams();
        
        // 4. Оптимизируем таблицы
        $this->optimizeTables();
        
        $this->info('Настройка завершена!');
        
        return Command::SUCCESS;
    }
    
    protected function checkDatabaseColumns()
    {
        $this->info('Проверка структуры базы данных...');
        
        $columns = [
            'documents' => ['embedding', 'search_indexed', 'is_parsed'],
            'search_terms' => ['term', 'term_type'],
            'document_ngrams' => ['ngram', 'document_id'],
        ];
        
        foreach ($columns as $table => $requiredColumns) {
            if (!Schema::hasTable($table)) {
                $this->error("Таблица {$table} не существует!");
                continue;
            }
            
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $this->warn("Колонка {$table}.{$column} отсутствует");
                }
            }
        }
    }
    
    protected function updateDocumentsStatus()
    {
        $this->info('Обновление статусов документов...');
        
        // Обновляем is_parsed для документов с текстом
        $updated = Document::whereNotNull('content_text')
            ->where('is_parsed', false)
            ->update([
                'is_parsed' => true,
                'parsing_quality' => DB::raw('LENGTH(content_text) / 1000'), // Примерная оценка
                'search_indexed' => false // Помечаем для индексации
            ]);
        
        $this->info("Обновлено {$updated} документов");
    }
    
    protected function createInitialNgrams()
    {
        $this->info('Создание начальных n-грамм...');
        
        // Очищаем существующие n-граммы
        DB::table('document_ngrams')->truncate();
        
        // Берем первые 50 документов для создания n-грамм
        $documents = Document::where('is_parsed', true)
            ->whereNotNull('content_text')
            ->limit(50)
            ->get();
        
        $bar = $this->output->createProgressBar($documents->count());
        $totalNgrams = 0;
        
        foreach ($documents as $document) {
            $text = mb_strtolower($document->content_text, 'UTF-8');
            $words = preg_split('/\s+/', $text);
            
            $words = array_filter($words, function($word) {
                return mb_strlen($word, 'UTF-8') > 2;
            });
            
            $words = array_values($words);
            
            // Создаем триграммы (последовательности из 3 слов)
            $ngrams = [];
            for ($i = 0; $i < count($words) - 2; $i++) {
                $ngram = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2];
                if (mb_strlen($ngram, 'UTF-8') <= 100) {
                    $ngrams[] = [
                        'document_id' => $document->id,
                        'ngram' => $ngram,
                        'ngram_type' => 'trigram',
                        'position' => $i,
                        'frequency' => 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }
            
            // Вставляем пачками по 1000
            $chunks = array_chunk($ngrams, 1000);
            foreach ($chunks as $chunk) {
                DB::table('document_ngrams')->insert($chunk);
            }
            
            $totalNgrams += count($ngrams);
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Создано {$totalNgrams} n-грамм");
    }
    
    protected function optimizeTables()
    {
        $this->info('Оптимизация таблиц...');
        
        $tables = ['documents', 'document_ngrams', 'search_terms', 'document_relevancy'];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("OPTIMIZE TABLE {$table}");
                $this->info("Таблица {$table} оптимизирована");
            }
        }
    }
}