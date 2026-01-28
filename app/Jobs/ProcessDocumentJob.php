<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\AdvancedDocumentParser;
use App\Services\DocumentIndexer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 час
    public $tries = 3;
    public $backoff = [60, 300, 600];
    
    protected $documentId;
    protected $documentParser;
    protected $documentIndexer;

    /**
     * Create a new job instance.
     */
    public function __construct($documentId)
    {
        $this->documentId = $documentId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Начало обработки документа в фоне: {$this->documentId}");
            
            $document = Document::findOrFail($this->documentId);
            
            // Обновляем статус
            $document->update(['status' => 'parsing']);
            
            // Инициализируем сервисы
            $this->documentParser = app(AdvancedDocumentParser::class);
            $this->documentIndexer = app(DocumentIndexer::class);
            
            // Полный парсинг
            $parseResult = $this->documentParser->parseFull($document);
            
            // Обновляем статус
            $document->update(['status' => 'indexing']);
            
            // Индексация
            $indexResult = $this->documentIndexer->indexDocument($document);
            
            // Финальный статус
            $document->update([
                'status' => 'processed',
                'processing_completed_at' => now()
            ]);
            
            Log::info("Документ успешно обработан: {$this->documentId}");
            
        } catch (\Exception $e) {
            Log::error("Ошибка обработки документа {$this->documentId}: " . $e->getMessage());
            
            if ($document ?? false) {
                $document->update([
                    'status' => 'error',
                    'content_text' => $e->getMessage()
                ]);
            }
            
            throw $e; // Позволяет очереди повторить попытку
        }
    }

    /**
     * Обработка неудачи задания.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Задание по обработке документа {$this->documentId} провалилось: " . $exception->getMessage());
        
        try {
            $document = Document::find($this->documentId);
            if ($document) {
                $document->update([
                    'status' => 'error',
                    'content_text' => 'Ошибка обработки: ' . $exception->getMessage()
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Не удалось обновить статус документа после ошибки: " . $e->getMessage());
        }
    }
}