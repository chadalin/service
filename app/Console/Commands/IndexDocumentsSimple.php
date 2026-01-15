<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use Illuminate\Support\Facades\Log;

class IndexDocumentsSimple extends Command
{
    protected $signature = 'search:index-simple 
                            {--limit=100 : Количество документов}
                            {--skip-indexed : Пропустить индексированные}';
    
    protected $description = 'Простая индексация документов';
    
    public function handle()
    {
        $limit = $this->option('limit');
        $skipIndexed = $this->option('skip-indexed');
        
        $query = Document::where('status', 'processed')
            ->where('is_parsed', true);
        
        if ($skipIndexed) {
            $query->where('search_indexed', false);
        }
        
        $documents = $query->limit($limit)->get();
        
        $this->info("Найдено документов для индексации: {$documents->count()}");
        
        if ($documents->isEmpty()) {
            $this->info("Нет документов для индексации");
            return Command::SUCCESS;
        }
        
        $bar = $this->output->createProgressBar($documents->count());
        
        foreach ($documents as $document) {
            try {
                // Простая индексация - помечаем как индексированный
                $document->update([
                    'search_indexed' => true,
                    'detected_section' => $this->detectSection($document->content_text),
                    'detected_system' => $this->detectSystem($document->content_text),
                    'detected_component' => $this->detectComponent($document->title),
                ]);
                
            } catch (\Exception $e) {
                Log::error("Ошибка индексации документа {$document->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('Индексация завершена!');
        
        return Command::SUCCESS;
    }
    
    protected function detectSection($text)
    {
        $text = mb_strtolower($text, 'UTF-8');
        
        $sections = [
            'двигатель' => ['двигатель', 'мотор', 'engine', 'цилиндр', 'поршень'],
            'трансмиссия' => ['трансмиссия', 'коробка', 'сцепление', 'transmission'],
            'тормоза' => ['тормоз', 'brake', 'колодки', 'диск'],
            'подвеска' => ['подвеска', 'амортизатор', 'suspension', 'стойка'],
            'электрика' => ['электрика', 'проводка', 'electrical', 'аккумулятор'],
            'кузов' => ['кузов', 'body', 'покраска', 'сварка'],
        ];
        
        foreach ($sections as $section => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $section;
                }
            }
        }
        
        return 'общее';
    }
    
    protected function detectSystem($text)
    {
        // Упрощенная логика
        $text = mb_strtolower($text, 'UTF-8');
        
        if (str_contains($text, 'тормоз')) return 'тормозная система';
        if (str_contains($text, 'двигатель')) return 'силовая установка';
        if (str_contains($text, 'электрик')) return 'электрооборудование';
        if (str_contains($text, 'подвеск')) return 'ходовая часть';
        
        return 'общая система';
    }
    
    protected function detectComponent($title)
    {
        $title = mb_strtolower($title, 'UTF-8');
        
        $components = [
            'генератор', 'стартер', 'аккумулятор', 'свеча', 'фильтр',
            'насос', 'ремень', 'цепь', 'датчик', 'клапан'
        ];
        
        foreach ($components as $component) {
            if (str_contains($title, $component)) {
                return $component;
            }
        }
        
        return 'общий компонент';
    }
}