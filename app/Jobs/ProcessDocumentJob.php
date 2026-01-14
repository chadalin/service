<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $document;
    public $timeout = 300; // 5 минут

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function handle()
    {
        try {
            $document = $this->document;
            $document->update(['status' => 'processing']);
            
            $filePath = $this->getFilePath($document);
            
            if (!file_exists($filePath)) {
                throw new \Exception("File not found: {$filePath}");
            }
            
            // Извлекаем текст в зависимости от типа файла
            $text = $this->extractText($filePath, $document->file_type);
            
            // Извлекаем ключевые слова
            $keywords = $this->extractKeywords($text);
            
            // Анализируем структуру
            $sections = $this->analyzeSections($text);
            
            // Обновляем документ
            $document->update([
                'content' => substr($text, 0, 50000), // Ограничиваем размер
                'keywords' => $keywords,
                'sections' => $sections,
                'word_count' => str_word_count($text),
                'status' => 'processed',
                'processed_at' => now()
            ]);
            
            \Log::info("Document {$document->id} processed successfully");
            
        } catch (\Exception $e) {
            $this->document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            \Log::error("Error processing document {$this->document->id}: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function getFilePath(Document $document): string
    {
        $paths = [
            storage_path('app/public/' . $document->file_path),
            storage_path('app/' . $document->file_path),
            public_path('storage/' . $document->file_path),
            $document->file_path,
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        throw new \Exception("File not found for document {$document->id}");
    }
    
    private function extractText(string $filePath, string $fileType): string
    {
        switch (strtolower($fileType)) {
            case 'pdf':
                return $this->extractTextFromPdf($filePath);
            case 'doc':
            case 'docx':
                return $this->extractTextFromWord($filePath);
            case 'txt':
                return file_get_contents($filePath);
            default:
                return '';
        }
    }
    
    private function extractTextFromPdf(string $filePath): string
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        } catch (\Exception $e) {
            \Log::error("Error parsing PDF: " . $e->getMessage());
            return '';
        }
    }
    
    private function extractTextFromWord(string $filePath): string
    {
        try {
            $phpWord = WordIOFactory::load($filePath);
            $text = '';
            
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    }
                }
            }
            
            return $text;
        } catch (\Exception $e) {
            \Log::error("Error parsing Word document: " . $e->getMessage());
            return '';
        }
    }
    
    private function extractKeywords(string $text): array
    {
        // Простая логика извлечения ключевых слов
        $words = str_word_count($text, 1);
        $wordCount = array_count_values($words);
        
        arsort($wordCount);
        
        // Исключаем стоп-слова
        $stopWords = ['the', 'and', 'for', 'that', 'this', 'with', 'from', 'are', 'was', 'were', 'has', 'have', 'had'];
        $keywords = array_slice(array_filter(array_keys($wordCount), function($word) use ($stopWords) {
            return !in_array(strtolower($word), $stopWords) && strlen($word) > 3;
        }), 0, 20);
        
        return $keywords;
    }
    
    private function analyzeSections(string $text): array
    {
        // Простой анализ структуры
        $lines = explode("\n", $text);
        $sections = [];
        $currentSection = '';
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            if (empty($trimmed)) continue;
            
            // Определяем заголовки (строка в верхнем регистре или с цифрами)
            if (preg_match('/^[A-Z][A-Z\s\d]+$/', $trimmed) || 
                preg_match('/^\d+\.\s+/', $trimmed) ||
                preg_match('/^(Глава|Раздел|Часть|Chapter|Section)/i', $trimmed)) {
                
                if ($currentSection) {
                    $sections[] = [
                        'title' => $currentSection,
                        'length' => strlen($currentSection)
                    ];
                }
                
                $currentSection = $trimmed;
            }
        }
        
        if ($currentSection) {
            $sections[] = [
                'title' => $currentSection,
                'length' => strlen($currentSection)
            ];
        }
        
        return $sections;
    }
}