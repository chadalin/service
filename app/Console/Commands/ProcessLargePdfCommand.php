<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Services\PdfChunkProcessor;
use Illuminate\Support\Facades\Log;

class ProcessLargePdfCommand extends Command
{
    protected $signature = 'pdf:process-large {documentId} {--chunk=10}';
    protected $description = 'Process large PDF files in chunks';

    public function handle()
    {
        $documentId = $this->argument('documentId');
        $chunkSize = $this->option('chunk');
        
        $document = Document::findOrFail($documentId);
        
        $this->info("Processing large PDF: {$document->title}");
        $this->info("File size: " . number_format(filesize(storage_path('app/public/' . $document->file_path)) / (1024*1024), 2) . " MB");
        
        $processor = new PdfChunkProcessor();
        $result = $processor->process($document, $chunkSize);
        
        if ($result['success']) {
            $this->info("✅ Successfully processed {$result['pages']} pages");
        } else {
            $this->error("❌ Error: {$result['error']}");
        }
    }
}