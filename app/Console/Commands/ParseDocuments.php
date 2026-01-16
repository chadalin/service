<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Services\FileParserService;

class ParseDocuments extends Command
{
    protected $signature = 'documents:parse {--limit=10} {--all}';
    protected $description = 'Парсинг документов';

    public function handle(FileParserService $parser)
    {
        $query = Document::readyForParsing();
        
        if ($this->option('all')) {
            $documents = $query->get();
        } else {
            $documents = $query->limit($this->option('limit'))->get();
        }
        
        $total = $documents->count();
        
        if ($total === 0) {
            $this->info('Нет документов для парсинга');
            return 0;
        }
        
        $this->info("Найдено документов для парсинга: {$total}");
        
        $bar = $this->output->createProgressBar($total);
        $success = 0;
        $errors = 0;
        
        foreach ($documents as $document) {
            try {
                $result = $parser->parseDocument($document);
                if ($result['success']) {
                    $success++;
                } else {
                    $errors++;
                    $this->error("Ошибка документа {$document->id}: " . $result['message']);
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("Исключение документа {$document->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Парсинг завершен!");
        $this->info("Успешно: {$success}, Ошибок: {$errors}");
        
        return 0;
    }
}