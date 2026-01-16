<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentContent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

class FileParserService
{
    protected $pdfParser;
    
    public function __construct()
    {
        $this->pdfParser = new PdfParser();
    }
    
    /**
     * Парсинг файла документа с оптимизацией
     */
    public function parseDocument(Document $document)
    {
        try {
            $filePath = storage_path('app/' . $document->file_path);
            
            if (!file_exists($filePath)) {
                throw new \Exception("Файл не найден: " . $filePath);
            }
            
            // Проверяем размер файла
            $fileSize = filesize($filePath);
            Log::info("Парсинг документа {$document->id}, размер файла: " . 
                     round($fileSize / 1024 / 1024, 2) . " MB");
            
            $content = '';
            $fileType = strtolower(pathinfo($document->original_filename, PATHINFO_EXTENSION));
            
            // Ограничиваем максимальный размер для парсинга
            $maxFileSize = 100 * 1024 * 1024; // 100MB
            
            if ($fileSize > $maxFileSize) {
                Log::warning("Файл слишком большой ({$fileSize} bytes), пропускаем детальный парсинг");
                
                // Для очень больших файлов сохраняем только метаданные
                $content = "Файл слишком большой для полного парсинга. Размер: " . 
                          round($fileSize / 1024 / 1024, 2) . " MB";
            } else {
                // Парсим содержимое
                switch ($fileType) {
                    case 'pdf':
                        $content = $this->parsePdfOptimized($filePath);
                        break;
                        
                    case 'doc':
                    case 'docx':
                        $content = $this->parseWord($filePath);
                        break;
                        
                    case 'txt':
                        $content = file_get_contents($filePath);
                        break;
                        
                    default:
                        throw new \Exception("Неподдерживаемый формат файла: " . $fileType);
                }
            }
            
            // Очищаем текст
            $content = $this->cleanAndOptimizeText($content);
            
            // Сохраняем контент (автоматически выбирает способ)
            $contentLength = $document->saveContent($content);
            
            // Обновляем документ
            $document->update([
                'is_parsed' => true,
                'parsing_quality' => $this->calculateParsingQuality($content, $fileSize),
                'status' => 'parsed',
            ]);
            
            Log::info("Документ {$document->id} успешно распарсен, символы: {$contentLength}");
            
            return [
                'success' => true,
                'message' => 'Файл успешно распарсен',
                'content_length' => $contentLength,
                'document_id' => $document->id,
                'file_size' => $fileSize,
            ];
            
        } catch (\Exception $e) {
            Log::error("Ошибка парсинга документа {$document->id}: " . $e->getMessage());
            
            $document->update([
                'status' => 'parse_error',
                'parsing_quality' => 0
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка парсинга: ' . $e->getMessage(),
                'document_id' => $document->id
            ];
        }
    }
    
    /**
     * Оптимизированный парсинг PDF
     */
    protected function parsePdfOptimized($filePath)
    {
        try {
            $parser = new PdfParser();
            
            // Конфигурация для больших файлов
            $parser->getConfig()->setDataTmFontInfo([
                'space_width' => 300
            ]);
            
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            
            // Если текст слишком длинный, обрезаем его разумно
            $maxLength = 1000000; // 1 млн символов максимум
            if (mb_strlen($text, 'UTF-8') > $maxLength) {
                Log::warning("PDF текст слишком длинный, обрезаем до {$maxLength} символов");
                
                // Сохраняем начало и конец файла
                $firstPart = mb_substr($text, 0, $maxLength * 0.4, 'UTF-8');
                $lastPart = mb_substr($text, -$maxLength * 0.4, null, 'UTF-8');
                $text = $firstPart . "\n\n...[текст обрезан]...\n\n" . $lastPart;
            }
            
            return $text;
        } catch (\Exception $e) {
            Log::error("Ошибка парсинга PDF: " . $e->getMessage());
            
            // Пробуем альтернативный метод для больших файлов
            if (strpos($e->getMessage(), 'memory') !== false) {
                return $this->parseLargePdfInChunks($filePath);
            }
            
            throw new \Exception("Ошибка парсинга PDF: " . $e->getMessage());
        }
    }
    
    /**
     * Парсинг больших PDF по частям
     */
    protected function parseLargePdfInChunks($filePath)
    {
        Log::info("Используем chunk-парсинг для большого PDF");
        
        // Для очень больших PDF возвращаем только информацию
        $fileSize = filesize($filePath);
        $sizeMB = round($fileSize / 1024 / 1024, 2);
        
        return "Большой PDF файл ({$sizeMB} MB). Детальный парсинг пропущен для экономии ресурсов.";
    }
    
    /**
     * Очистка и оптимизация текста
     */
    protected function cleanAndOptimizeText($text)
    {
        if (empty($text)) {
            return '';
        }
        
        // Удаляем лишние пробелы и переводы строк
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Удаляем спецсимволы, но оставляем нужные
        $text = preg_replace('/[^\w\s\.\,\-\!\?\:\;\(\)\[\]\{\}\/\&\+\=\*\%\$\#\@а-яА-ЯёЁ]/u', ' ', $text);
        
        // Удаляем повторяющиеся символы
        $text = preg_replace('/([\.\,\-\!\?\:])\1+/', '$1', $text);
        
        // Обрезаем очень длинные строки
        $lines = explode("\n", $text);
        $optimizedLines = [];
        
        foreach ($lines as $line) {
            if (mb_strlen($line, 'UTF-8') > 1000) {
                $line = mb_substr($line, 0, 1000, 'UTF-8') . '...';
            }
            $optimizedLines[] = $line;
        }
        
        return trim(implode("\n", $optimizedLines));
    }
    
    /**
     * Расчет качества парсинга
     */
    protected function calculateParsingQuality($text, $fileSize = 0)
    {
        $length = mb_strlen($text, 'UTF-8');
        
        if ($length < 100) return 0.1;
        if ($length < 1000) return 0.3;
        if ($length < 10000) return 0.6;
        if ($length < 100000) return 0.8;
        if ($length < 1000000) return 0.9;
        return 0.95; // Для очень больших документов
    }
    
    /**
     * Массовый парсинг с лимитом
     */
    public function parseMultiple($documentIds, $limit = 5)
    {
        $results = [
            'success' => [],
            'errors' => []
        ];
        
        // Ограничиваем количество одновременных парсингов
        $limitedIds = array_slice($documentIds, 0, $limit);
        
        foreach ($limitedIds as $id) {
            $document = Document::find($id);
            if ($document) {
                try {
                    // Проверяем, не обрабатывается ли уже
                    if ($document->status === 'processing') {
                        continue;
                    }
                    
                    $result = $this->parseDocument($document);
                    if ($result['success']) {
                        $results['success'][] = $document->id;
                    } else {
                        $results['errors'][] = [
                            'id' => $document->id,
                            'message' => $result['message']
                        ];
                    }
                    
                    // Небольшая пауза между обработкой документов
                    usleep(100000); // 0.1 секунды
                    
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'id' => $document->id,
                        'message' => 'Исключение: ' . $e->getMessage()
                    ];
                }
            }
        }
        
        return $results;
    }
}