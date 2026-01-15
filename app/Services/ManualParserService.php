<?php

namespace App\Services;

use App\Models\Document;
use App\Models\CarModel;
use App\Models\RepairCategory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use thiagoalessio\TesseractOCR\TesseractOCR;

class ManualParserService
{
    protected $pdfParser;
    
    public function __construct()
    {
        $this->pdfParser = new PdfParser();
    }
    
    /**
     * Парсинг загруженного документа
     */
    public function parseDocument(Document $document)
    {
        try {
            $filePath = storage_path('app/' . $document->file_path);
            
            if (!file_exists($filePath)) {
                throw new \Exception("Файл не найден: " . $filePath);
            }
            
            $content = '';
            $fileType = strtolower(pathinfo($document->original_filename, PATHINFO_EXTENSION));
            
            switch ($fileType) {
                case 'pdf':
                    $content = $this->parsePdf($filePath);
                    break;
                    
                case 'doc':
                case 'docx':
                    $content = $this->parseWord($filePath);
                    break;
                    
                case 'txt':
                    $content = file_get_contents($filePath);
                    break;
                    
                case 'jpg':
                case 'jpeg':
                case 'png':
                    $content = $this->parseImage($filePath);
                    break;
                    
                default:
                    throw new \Exception("Неподдерживаемый формат файла: " . $fileType);
            }
            
            // Извлекаем метаданные
            $metadata = $this->extractMetadata($content, $document);
            
            // Определяем категорию
            $categoryId = $this->detectCategory($content, $metadata);
            
            // Извлекаем ключевые слова
            $keywords = $this->extractKeywords($content);
            
            // Обновляем документ
            $document->update([
                'content_text' => $content,
                'keywords' => $keywords,
                'category_id' => $categoryId,
                'status' => 'processed'
            ]);
            
            Log::info("Документ {$document->id} успешно распарсен");
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Ошибка парсинга документа {$document->id}: " . $e->getMessage());
            $document->update(['status' => 'parse_error']);
            return false;
        }
    }
    
    /**
     * Парсинг PDF файла
     */
    protected function parsePdf($filePath)
    {
        try {
            $pdf = $this->pdfParser->parseFile($filePath);
            $text = $pdf->getText();
            
            // Улучшаем текст
            $text = $this->cleanText($text);
            
            return $text;
            
        } catch (\Exception $e) {
            Log::error("Ошибка парсинга PDF: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Парсинг Word документа
     */
    protected function parseWord($filePath)
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
            
            return $this->cleanText($text);
            
        } catch (\Exception $e) {
            Log::error("Ошибка парсинга Word: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Парсинг изображения (OCR)
     */
    protected function parseImage($filePath)
    {
        try {
            $ocr = new TesseractOCR($filePath);
            $ocr->lang('rus', 'eng');
            $text = $ocr->run();
            
            return $this->cleanText($text);
            
        } catch (\Exception $e) {
            Log::error("Ошибка OCR: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Извлечение метаданных
     */
    protected function extractMetadata($content, Document $document)
    {
        $metadata = [
            'brand' => null,
            'model' => null,
            'year' => null,
            'engine' => null,
            'section' => null
        ];
        
        // Проверяем, привязана ли модель автомобиля
        if ($document->car_model_id) {
            $carModel = CarModel::with('brand')->find($document->car_model_id);
            if ($carModel) {
                $metadata['brand'] = $carModel->brand->name;
                $metadata['model'] = $carModel->name;
                $metadata['year'] = $carModel->year_from . '-' . $carModel->year_to;
            }
        }
        
        // Ищем в тексте информацию о модели
        $contentLower = strtolower($content);
        
        // Популярные марки
        $brands = ['toyota', 'nissan', 'honda', 'bmw', 'mercedes', 'audi', 'volkswagen', 
                  'ford', 'chevrolet', 'hyundai', 'kia', 'mazda', 'subaru', 'lexus'];
        
        foreach ($brands as $brand) {
            if (strpos($contentLower, $brand) !== false) {
                $metadata['brand'] = ucfirst($brand);
                break;
            }
        }
        
        // Ищем год выпуска
        if (preg_match_all('/\b(19|20)\d{2}\b/', $content, $matches)) {
            $years = array_unique($matches[0]);
            sort($years);
            $metadata['year'] = implode('-', $years);
        }
        
        // Ищем информацию о двигателе
        if (preg_match('/(\d\.\dL?|\d\.\d\s*л|\d\s*литров?)/i', $content, $match)) {
            $metadata['engine'] = $match[1];
        }
        
        // Определяем раздел мануала
        $sections = [
            'двигатель' => ['двигатель', 'engine', 'мотор'],
            'трансмиссия' => ['трансмиссия', 'transmission', 'коробка', 'сцепление'],
            'тормоза' => ['тормоз', 'brake'],
            'подвеска' => ['подвеска', 'suspension', 'амортизатор'],
            'электрика' => ['электрика', 'electrical', 'проводка', 'аккумулятор'],
            'кузов' => ['кузов', 'body', 'покраска', 'сварка']
        ];
        
        foreach ($sections as $sectionName => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($contentLower, $keyword) !== false) {
                    $metadata['section'] = $sectionName;
                    break 2;
                }
            }
        }
        
        return $metadata;
    }
    
    /**
     * Определение категории ремонта
     */
    protected function detectCategory($content, $metadata)
    {
        $contentLower = strtolower($content);
        
        $categoryKeywords = [
            // Двигатель
            1 => ['двигатель', 'engine', 'мотор', 'цилиндр', 'поршень', 'коленвал', 'распредвал'],
            
            // Трансмиссия
            2 => ['трансмиссия', 'коробка передач', 'сцепление', 'transmission', 'clutch'],
            
            // Тормозная система
            3 => ['тормоз', 'brake', 'тормозной диск', 'колодки', 'суппорт'],
            
            // Подвеска
            4 => ['подвеска', 'suspension', 'амортизатор', 'стойка', 'рычаг', 'шаровая'],
            
            // Рулевое управление
            5 => ['рулевое', 'steering', 'рулевая рейка', 'насос', 'тяга'],
            
            // Электрика
            6 => ['электрика', 'electrical', 'проводка', 'аккумулятор', 'генератор', 'стартер'],
            
            // Кузов
            7 => ['кузов', 'body', 'покраска', 'сварка', 'крыло', 'дверь', 'капот'],
            
            // Система охлаждения
            8 => ['охлаждение', 'cooling', 'радиатор', 'термостат', 'помпа'],
            
            // Выхлопная система
            9 => ['выхлоп', 'exhaust', 'глушитель', 'катализатор', 'резонатор']
        ];
        
        // Проверяем по ключевым словам
        foreach ($categoryKeywords as $categoryId => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($contentLower, $keyword) !== false) {
                    return $categoryId;
                }
            }
        }
        
        // Если не нашли, пробуем по метаданным section
        if ($metadata['section']) {
            $sectionToCategory = [
                'двигатель' => 1,
                'трансмиссия' => 2,
                'тормоза' => 3,
                'подвеска' => 4,
                'электрика' => 6,
                'кузов' => 7
            ];
            
            if (isset($sectionToCategory[$metadata['section']])) {
                return $sectionToCategory[$metadata['section']];
            }
        }
        
        return null; // Категория не определена
    }
    
    /**
     * Извлечение ключевых слов
     */
    protected function extractKeywords($content)
    {
        // Удаляем стоп-слова
        $stopWords = ['и', 'в', 'на', 'с', 'по', 'для', 'или', 'но', 'а', 'же', 'ли',
                     'бы', 'что', 'как', 'так', 'это', 'то', 'из', 'от', 'до', 'у',
                     'за', 'к', 'о', 'об', 'со', 'во', 'не', 'ни', 'да', 'нет'];
        
        // Разбиваем текст на слова
        $words = str_word_count($content, 1, 'а-яА-ЯёЁa-zA-Z0-9');
        $words = array_map('strtolower', $words);
        
        // Удаляем стоп-слова и короткие слова
        $filteredWords = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });
        
        // Считаем частоту слов
        $wordCount = array_count_values($filteredWords);
        arsort($wordCount);
        
        // Берем топ-20 слов
        $keywords = array_slice(array_keys($wordCount), 0, 20);
        
        return implode(', ', $keywords);
    }
    
    /**
     * Очистка текста
     */
    protected function cleanText($text)
    {
        // Удаляем лишние пробелы
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Удаляем специальные символы, но оставляем пунктуацию
        $text = preg_replace('/[^\w\s\.\,\-\!\?\:\(\)\[\]\{\}\/\&\+\=\*\%\$\#\@а-яА-ЯёЁ]/u', ' ', $text);
        
        // Удаляем повторяющиеся символы
        $text = preg_replace('/([\.\,\-\!\?\:])\1+/', '$1', $text);
        
        // Удаляем лишние переводы строк
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        return trim($text);
    }
    
    /**
     * Создание поискового индекса
     */
    public function createSearchIndex(Document $document)
    {
        try {
            $text = $document->content_text;
            
            if (empty($text)) {
                return false;
            }
            
            // Создаем поисковый вектор (для PostgreSQL)
            if (config('database.default') === 'pgsql') {
                $searchVector = DB::raw("to_tsvector('russian', ?)");
                $document->update([
                    'search_vector' => $searchVector,
                    'search_indexed' => true
                ]);
            }
            
            // Создаем n-граммы для улучшенного поиска
            $this->createNGrams($document, $text);
            
            Log::info("Индекс создан для документа {$document->id}");
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Ошибка создания индекса для документа {$document->id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Создание n-грамм для улучшенного поиска
     */
    protected function createNGrams(Document $document, $text)
    {
        // Удаляем таблицу если существует
        Schema::dropIfExists('document_ngrams');
        
        // Создаем таблицу для n-грамм
        Schema::create('document_ngrams', function ($table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->string('ngram', 50);
            $table->integer('position');
            $table->index(['ngram', 'document_id']);
        });
        
        // Разбиваем текст на слова
        $words = preg_split('/\s+/', $text);
        $words = array_filter($words, function($word) {
            return strlen($word) > 2;
        });
        
        // Создаем 3-граммы
        $ngrams = [];
        for ($i = 0; $i < count($words) - 2; $i++) {
            $ngram = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2];
            $ngrams[] = [
                'document_id' => $document->id,
                'ngram' => substr($ngram, 0, 50),
                'position' => $i
            ];
        }
        
        // Сохраняем в базу пакетами
        $chunks = array_chunk($ngrams, 1000);
        foreach ($chunks as $chunk) {
            DB::table('document_ngrams')->insert($chunk);
        }
    }
}