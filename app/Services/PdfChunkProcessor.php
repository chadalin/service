<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Log;

class PdfChunkProcessor
{
    public function process(Document $document, $chunkSize = 10)
    {
        try {
            $pdfPath = storage_path('app/public/' . $document->file_path);
            
            if (!file_exists($pdfPath)) {
                return ['success' => false, 'error' => 'File not found'];
            }
            
            // Проверяем размер файла
            $fileSizeMB = filesize($pdfPath) / (1024 * 1024);
            
            if ($fileSizeMB > 100) {
                Log::warning("Large PDF detected: {$fileSizeMB} MB");
                // Увеличиваем чанк для очень больших файлов
                $chunkSize = 5;
            }
            
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($pdfPath);
            
            $processed = 0;
            
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo += $chunkSize) {
                $this->processChunk($document, $pdf, $pageNo, min($pageNo + $chunkSize - 1, $pageCount));
                $processed += min($chunkSize, $pageCount - $pageNo + 1);
                
                // Очистка памяти
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
                
                Log::info("Processed {$processed}/{$pageCount} pages");
            }
            
            return ['success' => true, 'pages' => $processed];
            
        } catch (\Exception $e) {
            Log::error("PDF processing error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function processChunk($document, $pdf, $startPage, $endPage)
    {
        for ($page = $startPage; $page <= $endPage; $page++) {
            try {
                // Обработка одной страницы
                $this->processSinglePage($document, $pdf, $page);
            } catch (\Exception $e) {
                Log::error("Error processing page {$page}: " . $e->getMessage());
                continue;
            }
        }
    }
    
    private function processSinglePage($document, $pdf, $pageNumber)
    {
        // ... ваш код обработки одной страницы ...
    }
}