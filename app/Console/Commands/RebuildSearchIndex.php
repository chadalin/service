<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Services\ManualParserService;
use Illuminate\Support\Facades\DB;

class RebuildSearchIndex extends Command
{
    protected $signature = 'search:rebuild 
                            {--all : Переиндексировать все документы}
                            {--truncate : Очистить таблицы индекса}';
    
    protected $description = 'Перестроение поискового индекса';
    
    public function handle(ManualParserService $parser)
    {
        if ($this->option('truncate')) {
            if ($this->confirm('Вы уверены, что хотите очистить все таблицы индекса?')) {
                DB::table('document_ngrams')->truncate();
                DB::table('document_relevancy')->truncate();
                DB::table('search_cache')->truncate();
                
                Document::query()->update([
                    'search_indexed' => false,
                    'embedding' => null,
                ]);
                
                $this->info("Таблицы индекса очищены.");
            }
        }
        
        if ($this->option('all')) {
            $documents = Document::all();
        } else {
            $documents = Document::where('search_indexed', false)->get();
        }
        
        $this->info("Переиндексация документов: {$documents->count()}");
        
        $bar = $this->output->createProgressBar($documents->count());
        $bar->start();
        
        foreach ($documents as $document) {
            try {
                $parser->createSearchIndex($document);
            } catch (\Exception $e) {
                $this->error("Ошибка индексации документа {$document->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Индекс перестроен!");
        
        return Command::SUCCESS;
    }
}