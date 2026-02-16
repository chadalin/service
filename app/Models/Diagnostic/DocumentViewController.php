<?php

namespace App\Http\Controllers\Diagnostic;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\DocumentImage;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentViewController extends Controller
{
    /**
     * Показать страницу документа по номеру
     */
    public function showPage($id, $pageNumber, Request $request)
    {
        try {
            $document = Document::with(['carModel.brand', 'category'])
                ->where('is_active', true)
                ->findOrFail($id);
            
            // Получаем страницу по номеру
            $page = DocumentPage::where('document_id', $id)
                ->where('page_number', $pageNumber)
                ->where('status', 'parsed')
                ->first();
            
            if (!$page) {
                // Если страницы нет, ищем ближайшую
                $page = DocumentPage::where('document_id', $id)
                    ->where('page_number', '>=', $pageNumber)
                    ->where('status', 'parsed')
                    ->orderBy('page_number')
                    ->first();
                
                if (!$page) {
                    return redirect()->route('documents.view', $id)
                        ->with('error', 'Страница не найдена');
                }
            }
            
            return $this->renderPage($document, $page, $request);
            
        } catch (\Exception $e) {
            Log::error('Public page view error: ' . $e->getMessage());
            return response()->view('errors.404', [], 404);
        }
    }
    
    /**
     * Рендер страницы с данными
     */
    private function renderPage($document, $page, Request $request)
    {
        $highlightTerm = $request->get('highlight', '');
        
        // Получаем изображения для этой страницы
        $images = DocumentImage::where('document_id', $document->id)
            ->where('page_number', $page->page_number)
            ->where('status', 'active')
            ->get()
            ->map(function($image) {
                $image->has_screenshot = $image->screenshot_path && 
                    Storage::disk('public')->exists($image->screenshot_path);
                
                if ($image->has_screenshot) {
                    $image->screenshot_url = Storage::url($image->screenshot_path);
                }
                
                return $image;
            });
        
        // Извлекаем скриншоты из контента
        $screenshots = $this->extractScreenshotsFromContent($page->content ?? '');
        
        // Разбиваем текст на абзацы
        $paragraphs = $this->splitTextIntoParagraphs($page->content_text ?? '', 5);
        
        // Извлекаем мета-информацию
        $metaInfo = $this->extractMetaInformation($page->content_text ?? '');
        
        // Получаем соседние страницы для навигации
        $prevPage = DocumentPage::where('document_id', $document->id)
            ->where('page_number', '<', $page->page_number)
            ->where('status', 'parsed')
            ->orderBy('page_number', 'desc')
            ->first();
        
        $nextPage = DocumentPage::where('document_id', $document->id)
            ->where('page_number', '>', $page->page_number)
            ->where('status', 'parsed')
            ->orderBy('page_number', 'asc')
            ->first();
        
        // Подготавливаем текст с подсветкой
        $highlightedContent = $highlightTerm ? 
            $this->highlightText($page->content_text ?? '', $highlightTerm) : 
            null;
        
        return view('documents.public.page', compact(
            'document',
            'page',
            'images',
            'screenshots',
            'paragraphs',
            'metaInfo',
            'prevPage',
            'nextPage',
            'highlightTerm',
            'highlightedContent'
        ));
    }
    
    /**
     * Извлекает скриншоты из HTML контента
     */
    private function extractScreenshotsFromContent($htmlContent)
    {
        if (empty($htmlContent)) {
            return [];
        }
        
        $screenshots = [];
        
        // Простой парсинг HTML для поиска изображений
        preg_match_all('/<img[^>]+src="([^">]+)"[^>]*>/i', $htmlContent, $matches);
        
        foreach ($matches[1] ?? [] as $index => $src) {
            // Проверяем, что это скриншот
            if (str_contains($src, 'document_pages_screenshots') || 
                str_contains($src, 'screenshot')) {
                
                // Получаем полный URL
                $fullUrl = $src;
                if (!str_starts_with($src, 'http')) {
                    if (str_starts_with($src, '/storage/')) {
                        $fullUrl = url($src);
                    } else {
                        $fullUrl = Storage::url($src);
                    }
                }
                
                $screenshots[] = [
                    'url' => $fullUrl,
                    'description' => 'Скриншот страницы ' . ($index + 1),
                    'is_main' => $index === 0
                ];
            }
        }
        
        return $screenshots;
    }
    
    /**
     * Разбивает текст на абзацы
     */
    private function splitTextIntoParagraphs($text, $linesPerParagraph = 5)
    {
        if (empty($text)) {
            return [];
        }
        
        $lines = explode("\n", trim($text));
        $paragraphs = [];
        $currentParagraph = [];
        
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            
            if (!empty($trimmedLine)) {
                $currentParagraph[] = $trimmedLine;
            }
            
            if (count($currentParagraph) >= $linesPerParagraph || empty($trimmedLine)) {
                if (!empty($currentParagraph)) {
                    $paragraphs[] = implode("\n", $currentParagraph);
                    $currentParagraph = [];
                }
            }
        }
        
        if (!empty($currentParagraph)) {
            $paragraphs[] = implode("\n", $currentParagraph);
        }
        
        return $paragraphs;
    }
    
    /**
     * Извлекает мета-информацию
     */
    private function extractMetaInformation($text)
    {
        $meta = [
            'title' => '',
            'keywords' => [],
            'description' => '',
            'instructions' => []
        ];
        
        if (empty($text)) {
            return $meta;
        }
        
        // Извлекаем заголовок (первая строка)
        $lines = explode("\n", trim($text));
        if (count($lines) > 0) {
            $firstLine = trim($lines[0]);
            if (mb_strlen($firstLine) < 100) {
                $meta['title'] = $firstLine;
            }
        }
        
        // Описание - первые 3 строки
        $descriptionLines = array_slice($lines, 0, 3);
        $meta['description'] = implode(' ', array_map('trim', $descriptionLines));
        
        // Ключевые слова
        $words = preg_split('/\s+/', mb_strtolower($text));
        $wordCount = array_count_values($words);
        arsort($wordCount);
        
        $stopWords = ['и', 'в', 'на', 'с', 'по', 'для', 'из', 'от', 'до', 'при'];
        $keywords = array_slice(array_filter(array_keys($wordCount), function($word) use ($stopWords) {
            return mb_strlen($word) > 2 && !in_array($word, $stopWords);
        }), 0, 10);
        
        $meta['keywords'] = array_unique($keywords);
        
        // Инструкции
        $instructionKeywords = ['инструкция', 'порядок', 'процедура', 'снятие', 'установка', 'замена'];
        foreach ($lines as $line) {
            $lowerLine = mb_strtolower($line);
            foreach ($instructionKeywords as $keyword) {
                if (str_contains($lowerLine, $keyword)) {
                    $meta['instructions'][] = trim($line);
                    break;
                }
            }
        }
        
        return $meta;
    }
    
    /**
     * Подсвечивает текст
     */
    private function highlightText($text, $searchTerm)
    {
        if (empty($text) || empty($searchTerm)) {
            return $text;
        }
        
        // Экранируем спецсимволы для регулярных выражений
        $escapedTerm = preg_quote($searchTerm, '/');
        
        // Подсвечиваем все вхождения
        $highlighted = preg_replace(
            "/($escapedTerm)/ui",
            '<mark class="bg-warning">$1</mark>',
            $text
        );
        
        return $highlighted;
    }
}