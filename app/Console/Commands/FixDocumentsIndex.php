<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use Illuminate\Support\Facades\DB;

class FixDocumentsIndex extends Command
{
    protected $signature = 'documents:fix-index {--all : Обработать все документы}';
    protected $description = 'Исправление индексации документов';

    public function handle()
    {
        $this->info('🔧 Исправление индексации документов...');

        // Получаем документы
        if ($this->option('all')) {
            $documents = Document::all();
        } else {
            $documents = Document::where('status', '!=', 'processed')
                ->orWhere('search_indexed', false)
                ->orWhereNull('detected_section')
                ->get();
        }

        $total = $documents->count();
        
        if ($total === 0) {
            $this->info('✅ Все документы уже обработаны.');
            return 0;
        }

        $this->info("📄 Найдено документов для обработки: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($documents as $document) {
            $this->processDocument($document);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        
        $this->info("🎉 Обработка завершена!");
        $this->showStats();
        
        return 0;
    }

    private function processDocument(Document $document)
    {
        // Определяем секцию по заголовку
        $section = $this->detectSectionFromTitle($document->title);
        
        // Определяем систему
        $system = $this->getSystemFromSection($section);
        
        // Определяем компонент
        $component = $this->detectComponentFromTitle($document->title);
        
        // Обрабатываем keywords
        $keywordsText = $this->processKeywords($document->keywords);
        
        // Если есть content_text, анализируем его для уточнения секции
        if (!empty($document->content_text)) {
            $contentSection = $this->detectSectionFromContent($document->content_text);
            if ($contentSection !== 'общее' && $section === 'общее') {
                $section = $contentSection;
                $system = $this->getSystemFromSection($section);
            }
        }
        
        // Обновляем документ
        $document->update([
            'status' => 'processed',
            'search_indexed' => true,
            'is_parsed' => !empty($document->content_text),
            'parsing_quality' => !empty($document->content_text) ? 0.8 : 0,
            'detected_section' => $section,
            'detected_system' => $system,
            'detected_component' => $component,
            'keywords_text' => $keywordsText,
        ]);
    }

    private function detectSectionFromTitle($title)
    {
        if (empty($title)) return 'общее';
        
        $title = mb_strtolower($title, 'UTF-8');
        
        $patterns = [
            'двигатель' => ['двигатель', 'мотор', 'engine', 'motor', 'двигател'],
            'трансмиссия' => ['трансмиссия', 'коробка', 'transmission', 'кпп', 'акпп'],
            'тормоза' => ['тормоз', 'brake', 'тормозн'],
            'электрика' => ['электрик', 'electrical', 'электр', 'проводк'],
            'подвеска' => ['подвеск', 'suspension', 'амортизатор'],
            'кузов' => ['кузов', 'body', 'покраск', 'сварк'],
            'рулевое' => ['рулевой', 'steering', 'рулев'],
            'топливо' => ['топливо', 'бензин', 'дизель', 'инжектор'],
            'охлаждение' => ['радиатор', 'охлаждение', 'cooling'],
            'выхлоп' => ['выхлоп', 'глушитель', 'exhaust'],
        ];
        
        foreach ($patterns as $section => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($title, $keyword)) {
                    return $section;
                }
            }
        }
        
        return 'общее';
    }

    private function detectSectionFromContent($content)
    {
        $content = mb_strtolower(substr($content, 0, 1000), 'UTF-8');
        
        $keywords = [
            'двигатель' => 0, 'трансмиссия' => 0, 'тормоз' => 0,
            'электрик' => 0, 'подвеск' => 0, 'кузов' => 0,
        ];
        
        foreach ($keywords as $word => &$count) {
            $count = substr_count($content, $word);
        }
        
        arsort($keywords);
        $topSection = array_key_first($keywords);
        
        return $keywords[$topSection] > 0 ? $topSection : 'общее';
    }

    private function getSystemFromSection($section)
    {
        $map = [
            'двигатель' => 'силовая установка',
            'трансмиссия' => 'трансмиссия',
            'тормоза' => 'тормозная система',
            'электрика' => 'электрооборудование',
            'подвеска' => 'ходовая часть',
            'кузов' => 'кузов и элементы',
            'рулевое' => 'рулевое управление',
            'топливо' => 'топливная система',
            'охлаждение' => 'система охлаждения',
            'выхлоп' => 'выхлопная система',
            'общее' => 'общая информация',
        ];
        
        return $map[$section] ?? 'неизвестно';
    }

    private function detectComponentFromTitle($title)
    {
        if (empty($title)) return 'основной компонент';
        
        $title = mb_strtolower($title, 'UTF-8');
        
        $components = [
            'генератор', 'стартер', 'аккумулятор', 'свеча',
            'фильтр', 'насос', 'ремень', 'цепь',
            'датчик', 'клапан', 'радиатор', 'термостат',
            'амортизатор', 'пружина', 'диск', 'колодка',
            'суппорт', 'турбина', 'компрессор', 'инжектор',
        ];
        
        foreach ($components as $component) {
            if (str_contains($title, mb_strtolower($component, 'UTF-8'))) {
                return $component;
            }
        }
        
        return 'основной компонент';
    }

    private function processKeywords($keywords)
    {
        if (empty($keywords)) {
            return 'руководство, документация, ремонт';
        }
        
        // Если это JSON
        if (is_string($keywords) && (str_starts_with($keywords, '[') || str_starts_with($keywords, '{'))) {
            try {
                $decoded = json_decode($keywords, true);
                if (is_array($decoded)) {
                    return implode(', ', array_filter($decoded, 'is_string'));
                }
            } catch (\Exception $e) {
                // Не JSON
            }
        }
        
        // Если это массив
        if (is_array($keywords)) {
            return implode(', ', array_filter($keywords, 'is_string'));
        }
        
        // Строка
        return (string)$keywords;
    }

    private function showStats()
    {
        $this->info("\n📊 Статистика после обработки:");
        
        $stats = Document::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "processed" THEN 1 ELSE 0 END) as processed,
            SUM(CASE WHEN search_indexed = 1 THEN 1 ELSE 0 END) as indexed,
            SUM(CASE WHEN is_parsed = 1 THEN 1 ELSE 0 END) as parsed
        ')->first();
        
        $this->table(
            ['Метрика', 'Значение', 'Процент'],
            [
                ['Всего документов', $stats->total, '100%'],
                ['Обработано', $stats->processed, round(($stats->processed/$stats->total)*100, 1) . '%'],
                ['Индексировано', $stats->indexed, round(($stats->indexed/$stats->total)*100, 1) . '%'],
                ['Распарсено', $stats->parsed, round(($stats->parsed/$stats->total)*100, 1) . '%'],
            ]
        );
        
        // Секции
        $sections = Document::select('detected_section', DB::raw('COUNT(*) as count'))
            ->whereNotNull('detected_section')
            ->groupBy('detected_section')
            ->orderBy('count', 'desc')
            ->get();
        
        if ($sections->isNotEmpty()) {
            $this->info("\n📁 Распределение по секциям:");
            $data = [];
            foreach ($sections as $section) {
                $data[] = [$section->detected_section, $section->count];
            }
            $this->table(['Секция', 'Количество'], $data);
        }
        
        // Примеры
        $this->info("\n📄 Примеры документов:");
        $examples = Document::select('id', 'title', 'detected_section', 'detected_system')
            ->limit(3)
            ->get();
        
        foreach ($examples as $doc) {
            echo "✅ #{$doc->id}: {$doc->title}\n";
            echo "   Секция: {$doc->detected_section}, Система: {$doc->detected_system}\n";
        }
    }
}