<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Services\ManualParserService;
use Illuminate\Support\Facades\Log;

class IndexDocuments extends Command
{
    protected $signature = 'search:index 
                            {--limit=100 : Количество документов для индексации}
                            {--force : Принудительная переиндексация}
                            {--skip-parsed : Пропустить уже распарсенные документы}';
    
    protected $description = 'Индексация документов для поиска';
    
    public function handle(ManualParserService $parser)
    {
        $limit = $this->option('limit');
        $force = $this->option('force');
        $skipParsed = $this->option('skip-parsed');
        
        $query = Document::query();
        
        if (!$force) {
            $query->where('status', '!=', 'processed')
                  ->orWhereNull('search_indexed');
        }
        
        if ($skipParsed) {
            $query->where('is_parsed', false);
        }
        
        $documents = $query->limit($limit)->get();
        
        $this->info("Найдено документов для индексации: {$documents->count()}");
        
        $bar = $this->output->createProgressBar($documents->count());
        $bar->start();
        
        $success = 0;
        $errors = 0;
        
        foreach ($documents as $document) {
            try {
                // Парсинг документа
                $parseResult = $parser->parseDocument($document);
                
                if ($parseResult) {
                    // Создание поискового индекса
                    $parser->createSearchIndex($document);
                    $success++;
                } else {
                    $errors++;
                    Log::warning("Не удалось распарсить документ ID: {$document->id}");
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error("Ошибка индексации документа ID: {$document->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Индексация завершена!");
        $this->info("Успешно: {$success}, Ошибок: {$errors}");
        
        if ($errors > 0) {
            $this->warn("Некоторые документы не были проиндексированы. Проверьте логи.");
        }
        
        return Command::SUCCESS;
    }
}