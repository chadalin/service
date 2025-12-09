<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Jobs\ProcessDocumentJob;

class ProcessAllDocuments extends Command
{
    protected $signature = 'documents:process {--id=} {--all}';
    protected $description = 'Process documents with OCR and text extraction';

    public function handle()
    {
        if ($this->option('id')) {
            $document = Document::find($this->option('id'));
            if (!$document) {
                $this->error("Document not found!");
                return;
            }
            $this->processDocument($document);
        } elseif ($this->option('all')) {
            $documents = Document::where('status', 'processing')->get();
            $this->info("Processing {$documents->count()} documents...");
            
            foreach ($documents as $document) {
                $this->processDocument($document);
            }
        } else {
            $this->info("Usage:");
            $this->info(" - Process specific document: php artisan documents:process --id=1");
            $this->info(" - Process all pending documents: php artisan documents:process --all");
        }
    }

    private function processDocument(Document $document)
    {
        $this->info("Processing document ID: {$document->id} - {$document->title}");
        
        try {
            ProcessDocumentJob::dispatchSync($document);
            $document->refresh();
            $this->info("✓ Status: {$document->status}");
        } catch (\Exception $e) {
            $this->error("✗ Error: " . $e->getMessage());
        }
    }
}