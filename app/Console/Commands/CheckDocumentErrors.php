<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;

class CheckDocumentErrors extends Command
{
    protected $signature = 'documents:check-errors';
    protected $description = 'Check document processing errors';

    public function handle()
    {
        $errorDocs = Document::where('status', 'error')->get();
        
        $this->info("Found {$errorDocs->count()} documents with errors:");
        
        foreach ($errorDocs as $doc) {
            $this->error("---");
            $this->error("ID: {$doc->id}");
            $this->error("Title: {$doc->title}");
            $this->error("File: {$doc->file_path}");
            $this->error("Type: {$doc->file_type}");
            
            // Проверим существование файла
            $filePath = storage_path('app/' . $doc->file_path);
            $this->error("File exists: " . (file_exists($filePath) ? 'YES' : 'NO'));
            
            if (file_exists($filePath)) {
                $this->error("File size: " . filesize($filePath) . " bytes");
            }
        }
        
        // Проверим последние логи
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $this->info("\n📋 Last log entries related to documents:");
            $logs = shell_exec("tail -50 " . escapeshellarg($logFile) . " | grep -i document");
            echo $logs ?: "No relevant log entries found.\n";
        }
    }
}