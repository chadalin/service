<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Services\DocumentProcessor;

class ProcessAllDocuments extends Command
{
    protected $signature = 'documents:process-all';
    protected $description = 'Process all unprocessed documents';
    
    public function handle()
    {
        $documents = Document::where('status', '!=', 'processed')->get();
        
        $this->info("Found {$documents->count()} documents to process");
        
        $processor = new DocumentProcessor();
        
        $bar = $this->output->createProgressBar($documents->count());
        
        foreach ($documents as $document) {
            try {
                $processor->processDocument($document);
                $this->info("\n✅ Processed: {$document->title}");
            } catch (\Exception $e) {
                $this->error("\n❌ Failed: {$document->title} - " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->info("\n\nProcessing complete!");
    }
}