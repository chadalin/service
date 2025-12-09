<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;

class DocumentProcessor
{
    protected $pdfParser;

    public function __construct()
    {
        $this->pdfParser = new PdfParser();
    }

    public function processDocument(Document $document)
{
    \Log::info("Processing document ID: {$document->id}");

    try {
        $fullPath = storage_path('app/' . $document->file_path);
        \Log::info("File path: {$fullPath}");

        if (!file_exists($fullPath)) {
            throw new \Exception("File not found: {$fullPath}");
        }

        // Простая обработка
        $content = "Document: " . $document->title . "\n\n";
        $content .= "This is a placeholder for file processing.\n";
        $content .= "Original file: " . $document->original_filename . "\n";
        $content .= "File type: " . $document->file_type . "\n\n";
        
        // Для текстовых файлов читаем содержимое
        if ($document->file_type === 'txt') {
            $fileContent = file_get_contents($fullPath);
            $content .= "File content:\n" . substr($fileContent, 0, 1000);
        } else {
            $content .= "For testing search, use text files (.txt) format.";
        }

        $document->update([
            'content_text' => $content,
            'status' => 'processed'
        ]);

        \Log::info("Document {$document->id} processed successfully");

    } catch (\Exception $e) {
        \Log::error("Error processing document {$document->id}: " . $e->getMessage());
        $document->update(['status' => 'error']);
    }
}

    private function processPdf(string $filePath): string
    {
        try {
            $pdf = $this->pdfParser->parseFile($filePath);
            $text = $pdf->getText();
            
            if (empty(trim($text))) {
                throw new \Exception("No text extracted from PDF");
            }
            
            return $text;
        } catch (\Exception $e) {
            Log::error("PDF processing error: " . $e->getMessage());
            throw new \Exception("Failed to process PDF: " . $e->getMessage());
        }
    }

    private function processWord(string $filePath): string
    {
        try {
            $phpWord = IOFactory::load($filePath);
            $content = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getElements')) {
                        foreach ($element->getElements() as $childElement) {
                            if (method_exists($childElement, 'getText')) {
                                $content .= $childElement->getText() . "\n";
                            }
                        }
                    }
                    if (method_exists($element, 'getText')) {
                        $content .= $element->getText() . "\n";
                    }
                }
            }

            return $content;
        } catch (\Exception $e) {
            Log::error("Word processing error: " . $e->getMessage());
            throw new \Exception("Failed to process Word document: " . $e->getMessage());
        }
    }

    private function processText(string $filePath): string
    {
        $content = file_get_contents($filePath);
        return $content ?: '';
    }

    private function cleanContent(string $content): string
    {
        // Удаляем лишние пробелы и переносы
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Удаляем специальные символы, но сохраняем кириллицу и пунктуацию
        $content = preg_replace('/[^\x{0410}-\x{044F}\x{0401}\x{0451}a-zA-Z0-9\s\.\,\-\!\\?\(\)\:\;]/u', ' ', $content);
        
        return trim($content);
    }

    public function extractKeywords(string $content): array
    {
        // Удаляем стоп-слова и выделяем ключевые слова
        $stopWords = ['и', 'в', 'на', 'с', 'по', 'для', 'из', 'от', 'до', 'за', 'к', 'у', 'о', 'об', 'не', 'что', 'это', 'как'];
        
        $words = preg_split('/\s+/', mb_strtolower($content));
        $words = array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && mb_strlen($word) > 2;
        });
        
        $wordFreq = array_count_values($words);
        arsort($wordFreq);
        
        return array_slice(array_keys($wordFreq), 0, 20);
    }
}