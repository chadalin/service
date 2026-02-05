<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateFulltextIndexes extends Command
{
    protected $signature = 'search:create-indexes {--force : Force create indexes}';
    protected $description = 'Create FULLTEXT indexes for search optimization';
    
    public function handle()
    {
        $this->info('Checking and creating FULLTEXT indexes...');
        
        $created = [];
        
        // 1. Проверяем и создаем индекс для document_pages
        try {
            $this->info('Checking document_pages table...');
            $existingIndexes = DB::select("
                SHOW INDEX FROM document_pages 
                WHERE Index_type = 'FULLTEXT'
            ");
            
            if (empty($existingIndexes) || $this->option('force')) {
                $this->info('Creating FULLTEXT index for document_pages...');
                
                DB::statement("
                    CREATE FULLTEXT INDEX ft_content_section 
                    ON document_pages (content_text, section_title)
                ");
                
                $created[] = 'document_pages (content_text, section_title)';
                $this->info('✓ Created index for document_pages');
            } else {
                $this->info('✓ Index already exists for document_pages');
            }
        } catch (\Exception $e) {
            $this->error('Error with document_pages: ' . $e->getMessage());
            Log::error('Failed to create index for document_pages: ' . $e->getMessage());
        }
        
        // 2. Проверяем и создаем индекс для documents
        try {
            $this->info('Checking documents table...');
            $existingDocIndexes = DB::select("
                SHOW INDEX FROM documents 
                WHERE Index_type = 'FULLTEXT'
            ");
            
            if (empty($existingDocIndexes) || $this->option('force')) {
                $this->info('Creating FULLTEXT index for documents...');
                
                DB::statement("
                    CREATE FULLTEXT INDEX ft_document_search 
                    ON documents (title, content_text, detected_system, detected_component)
                ");
                
                $created[] = 'documents (title, content_text, detected_system, detected_component)';
                $this->info('✓ Created index for documents');
            } else {
                $this->info('✓ Index already exists for documents');
            }
        } catch (\Exception $e) {
            $this->error('Error with documents: ' . $e->getMessage());
            Log::error('Failed to create index for documents: ' . $e->getMessage());
        }
        
        // 3. Проверяем тип таблицы (важно для FULLTEXT)
        $this->info("\nChecking table engines...");
        try {
            $tables = ['document_pages', 'documents'];
            foreach ($tables as $table) {
                $tableInfo = DB::select("SHOW TABLE STATUS LIKE '{$table}'");
                if (!empty($tableInfo)) {
                    $engine = $tableInfo[0]->Engine;
                    $this->info("  {$table}: {$engine} engine");
                    
                    if ($engine === 'MyISAM') {
                        $this->comment("  Note: MyISAM supports FULLTEXT");
                    } elseif ($engine === 'InnoDB') {
                        $this->comment("  Note: InnoDB supports FULLTEXT (MySQL 5.6+)");
                    } else {
                        $this->error("  Warning: {$engine} may not support FULLTEXT!");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('Error checking table engines: ' . $e->getMessage());
        }
        
        // 4. Проверяем версию MySQL
        try {
            $version = DB::select('SELECT VERSION() as version');
            if (!empty($version)) {
                $this->info("\nMySQL Version: " . $version[0]->version);
                
                // Проверяем версию для поддержки InnoDB FULLTEXT
                if (version_compare($version[0]->version, '5.6.0', '<')) {
                    $this->error("Warning: MySQL < 5.6 doesn't support FULLTEXT for InnoDB!");
                    $this->error("Consider upgrading MySQL or converting tables to MyISAM");
                }
            }
        } catch (\Exception $e) {
            $this->error('Error checking MySQL version: ' . $e->getMessage());
        }
        
        // 5. Результаты
        if (empty($created)) {
            $this->info("\nAll indexes already exist.");
        } else {
            $this->info("\nCreated indexes:");
            foreach ($created as $index) {
                $this->line("  ✓ {$index}");
            }
        }
        
        // 6. Тестируем поиск
        $this->info("\nTesting search...");
        $testQueries = ['двигатель', 'масло'];
        foreach ($testQueries as $query) {
            try {
                $start = microtime(true);
                $results = DB::table('document_pages')
                    ->where('content_text', 'LIKE', "%{$query}%")
                    ->limit(3)
                    ->count();
                $time = round((microtime(true) - $start) * 1000, 2);
                
                $this->info("  Query '{$query}': {$results} results, {$time}ms (LIKE)");
                
                // Пробуем FULLTEXT если индексы созданы
                if (!empty($created)) {
                    $start = microtime(true);
                    try {
                        $ftResults = DB::table('document_pages')
                            ->whereRaw("MATCH(content_text, section_title) AGAINST(? IN BOOLEAN MODE)", ["+{$query}*"])
                            ->limit(3)
                            ->count();
                        $ftTime = round((microtime(true) - $start) * 1000, 2);
                        $this->info("  Query '{$query}': {$ftResults} results, {$ftTime}ms (FULLTEXT)");
                    } catch (\Exception $e) {
                        $this->error("  FULLTEXT error: " . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                $this->error("  Error testing '{$query}': " . $e->getMessage());
            }
        }
        
        return Command::SUCCESS;
    }
}