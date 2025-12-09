<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Document;
use App\Services\DocumentProcessor;
use Illuminate\Support\Facades\Log;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $document;
    public $tries = 3;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function handle()
    {
        try {
            Log::info("🔄 Starting document processing job for ID: {$this->document->id}");
            
            // Обновляем статус на processing
            $this->document->update(['status' => 'processing']);
            
            $processor = new DocumentProcessor();
            
            Log::info("📄 Processing document: {$this->document->title}");
            Log::info("📁 File path: {$this->document->file_path}");
            Log::info("📝 File type: {$this->document->file_type}");
            
            // Обрабатываем документ
            $processor->processDocument($this->document);
            
            // Проверяем результат
            $this->document->refresh();
            
            if ($this->document->status === 'processed') {
                Log::info("✅ Document {$this->document->id} processed successfully!");
                Log::info("📊 Content length: " . strlen($this->document->content_text ?? ''));
            } else {
                Log::error("❌ Document {$this->document->id} processing failed. Status: {$this->document->status}");
            }
            
        } catch (\Exception $e) {
            Log::error("💥 Error processing document {$this->document->id}: " . $e->getMessage());
            Log::error("📋 Stack trace: " . $e->getTraceAsString());
            
            $this->document->update(['status' => 'error']);
            throw $e;
        }
    }

    public function failed(\Exception $exception)
    {
        Log::error("🚨 Job failed for document {$this->document->id}: " . $exception->getMessage());
        $this->document->update(['status' => 'error']);
    }
}