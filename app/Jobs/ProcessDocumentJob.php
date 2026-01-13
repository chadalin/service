<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $document;
    public $tries = 3;
    public $timeout = 300; // 5 минут

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function handle()
    {
        try {
            Log::info("🔄 Starting job for document ID: {$this->document->id}");
            
            // Обновляем статус на processing
            $this->document->update(['status' => 'processing']);
            
            // Создаем экземпляр процессора
            $processor = new \App\Services\DocumentProcessor();
            
            // Обрабатываем документ
            $success = $processor->processDocument($this->document);
            
            // Проверяем результат
            $this->document->refresh();
            
            if ($success && $this->document->status === 'processed') {
                Log::info("✅ SUCCESS: Document {$this->document->id} processed");
                
                // Отладочная информация
                $hasContent = !empty($this->document->content_text);
                $hasKeywords = !empty($this->document->keywords);
                
                Log::info("📊 Has content: " . ($hasContent ? 'YES' : 'NO'));
                Log::info("📏 Content length: " . ($hasContent ? strlen($this->document->content_text) : '0'));
                Log::info("🔑 Has keywords: " . ($hasKeywords ? 'YES' : 'NO'));
                
                if ($hasKeywords) {
                    $keywords = json_decode($this->document->keywords, true);
                    Log::info("🗝️ Keywords count: " . count($keywords));
                }
            } else {
                Log::error("❌ FAILED: Document status is {$this->document->status}");
            }
            
        } catch (Throwable $e) {
            Log::error("💥 JOB ERROR: " . $e->getMessage());
            Log::error("📄 Stack trace: " . $e->getTraceAsString());
            
            $this->document->update([
                'status' => 'error',
                'content_text' => 'Error: ' . $e->getMessage()
            ]);
            
            // Повторно выбрасываем исключение для failed()
            throw $e;
        }
    }

    public function failed(Throwable $exception)
    {
        Log::error("🚨 Job failed for document {$this->document->id}: " . $exception->getMessage());
        Log::error("📄 Stack trace: " . $exception->getTraceAsString());
        
        // Пытаемся обновить статус документа
        try {
            $this->document->update([
                'status' => 'error',
                'content_text' => 'Job failed: ' . $exception->getMessage()
            ]);
        } catch (Throwable $e) {
            Log::error("⚠️ Could not update document status: " . $e->getMessage());
        }
    }
}