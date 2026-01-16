<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ReindexDocuments extends Command
{
    protected $signature = 'documents:reindex {--limit=50} {--force}';
    protected $description = 'Переиндексация всех документов';

    public function handle()
    {
        $this->info('🔄 Начинаем переиндексацию документов...');

        // 1. Проверяем и добавляем колонки если нужно
        $this->ensureColumnsExist();

        // 2. Получаем документы
        $query = DB::table('documents');
        
        if (!$this->option('force')) {
            $query->where('status', '!=', 'processed')
                  ->orWhere('search_indexed', false)
                  ->orWhereNull('search_indexed');
        }
        
        $documents = $query->limit($this->option('limit'))->get();
        
        $total = $documents->count();
        
        if ($total === 0) {
            $this->info('✅ Все документы уже проиндексированы.');
            $this->showStats();
            return 0;
        }
        
        $this->info("📄 Найдено документов для индексации: {$total}");
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        $success = 0;
        $errors = 0;
        
        foreach ($documents as $doc) {
            try {
                $this->indexDocument($doc);
                $success++;
            } catch (\Exception $e) {
                $errors++;
                Log::error("Ошибка индексации документа {$doc->id}: " . $e->getMessage());
                $this->error("\n❌ Ошибка документа {$doc->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("🎉 Переиндексация завершена!");
        $this->info("✅ Успешно: {$success}, ❌ Ошибок: {$errors}");
        
        $this->showStats();
        
        return 0;
    }
    
    private function ensureColumnsExist()
    {
        $this->info('🔧 Проверяем структуру таблицы...');
        
        $columns = [
            'search_indexed' => "ALTER TABLE documents ADD COLUMN IF NOT EXISTS search_indexed BOOLEAN DEFAULT FALSE",
            'is_parsed' => "ALTER TABLE documents ADD COLUMN IF NOT EXISTS is_parsed BOOLEAN DEFAULT FALSE",
            'parsing_quality' => "ALTER TABLE documents ADD COLUMN IF NOT EXISTS parsing_quality DECIMAL(3,2) NULL",
            'detected_section' => "ALTER TABLE documents ADD COLUMN IF NOT EXISTS detected_section VARCHAR(100) NULL",
            'detected_system' => "ALTER TABLE documents ADD COLUMN IF NOT EXISTS detected_system VARCHAR(100) NULL",
            'detected_component' => "ALTER TABLE documents ADD COLUMN IF NOT EXISTS detected_component VARCHAR(100) NULL",
            'search_count' => "ALTER TABLE documents ADD COLUMN IF NOT EXISTS search_count INT DEFAULT 0",
            'view_count' => "ALTER TABLE documents ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0",
            'average_relevance' => "ALTER TABLE documents ADD COLUMN IF NOT EXISTS average_relevance DECIMAL(3,2) NULL",
        ];
        
        foreach ($columns as $name => $sql) {
            if (!Schema::hasColumn('documents', $name)) {
                try {
                    DB::statement($sql);
                    $this->info("   ✅ Добавлена колонка: {$name}");
                } catch (\Exception $e) {
                    $this->warn("   ⚠️ Не удалось добавить {$name}: " . $e->getMessage());
                }
            }
        }
        
        // Создаем FULLTEXT индекс если его нет
        try {
            $indexes = DB::select("SHOW INDEX FROM documents WHERE Key_name = 'doc_fulltext_idx'");
            if (empty($indexes)) {
                DB::statement("ALTER TABLE documents ADD FULLTEXT doc_fulltext_idx (title, content_text)");
                $this->info("   ✅ Создан FULLTEXT индекс");
            }
        } catch (\Exception $e) {
            $this->warn("   ⚠️ Не удалось создать FULLTEXT индекс: " . $e->getMessage());
        }
    }
    
    private function indexDocument($document)
    {
        $this->line("\n📝 Обрабатываем документ #{$document->id}: " . substr($document->title ?? 'Без названия', 0, 50));
        
        // Определяем секцию на основе контента
        $section = $this->detectSection($document);
        $system = $this->detectSystem($section);
        $component = $this->detectComponent($document);
        
        // Подготавливаем keywords_text
        $keywordsText = $this->prepareKeywordsText($document->keywords ?? null);
        
        // Обновляем документ ПРЯМЫМ SQL запросом
        DB::table('documents')
            ->where('id', $document->id)
            ->update([
                'status' => 'processed',
                'search_indexed' => true,
                'is_parsed' => !empty($document->content_text),
                'parsing_quality' => !empty($document->content_text) ? 0.8 : 0,
                'detected_section' => $section,
                'detected_system' => $system,
                'detected_component' => $component,
                'keywords_text' => $keywordsText,
                'updated_at' => now(),
            ]);
        
        $this->line("   ✅ Обновлено: секция='{$section}', система='{$system}'");
    }
    
    private function detectSection($document)
    {
        $text = '';
        if (!empty($document->title)) {
            $text .= ' ' . mb_strtolower($document->title, 'UTF-8');
        }
        if (!empty($document->content_text)) {
            $text .= ' ' . mb_strtolower(substr($document->content_text, 0, 1000), 'UTF-8');
        }
        if (!empty($document->keywords)) {
            $text .= ' ' . mb_strtolower($document->keywords, 'UTF-8');
        }
        
        $sections = [
            'двигатель' => ['двигатель', 'мотор', 'engine', 'motor', 'цилиндр', 'поршень'],
            'трансмиссия' => ['трансмиссия', 'коробка', 'сцепление', 'transmission', 'кпп'],
            'тормоза' => ['тормоз', 'brake', 'колодки', 'суппорт'],
            'подвеска' => ['подвеска', 'амортизатор', 'suspension', 'стойка'],
            'электрика' => ['электрика', 'электрическ', 'electrical', 'проводка', 'аккумулятор'],
            'кузов' => ['кузов', 'body', 'покраска', 'сварка'],
            'рулевое' => ['рулевой', 'steering', 'рейка'],
            'топливо' => ['топливо', 'бензин', 'дизель', 'инжектор'],
            'охлаждение' => ['радиатор', 'охлаждение', 'cooling'],
            'выхлоп' => ['выхлоп', 'глушитель', 'exhaust'],
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
    
    private function detectSystem($section)
    {
        $systems = [
            'двигатель' => 'силовая установка',
            'трансмиссия' => 'трансмиссия',
            'тормоза' => 'тормозная система',
            'подвеска' => 'ходовая часть',
            'электрика' => 'электрооборудование',
            'кузов' => 'кузов и элементы',
            'рулевое' => 'рулевое управление',
            'топливо' => 'топливная система',
            'охлаждение' => 'система охлаждения',
            'выхлоп' => 'выхлопная система',
            'общее' => 'общая информация',
        ];
        
        return $systems[$section] ?? 'неизвестно';
    }
    
    private function detectComponent($document)
    {
        $text = '';
        if (!empty($document->title)) {
            $text .= ' ' . mb_strtolower($document->title, 'UTF-8');
        }
        
        $components = [
            'генератор', 'стартер', 'аккумулятор', 'свеча', 'фильтр',
            'насос', 'ремень', 'цепь', 'датчик', 'клапан',
            'радиатор', 'термостат', 'амортизатор', 'пружина',
            'диск', 'колодка', 'суппорт', 'турбина',
        ];
        
        foreach ($components as $component) {
            if (str_contains($text, mb_strtolower($component, 'UTF-8'))) {
                return $component;
            }
        }
        
        return 'основной компонент';
    }
    
    private function prepareKeywordsText($keywords)
    {
        if (empty($keywords)) {
            return null;
        }
        
        // Если keywords выглядит как JSON
        if (is_string($keywords) && (str_starts_with($keywords, '[') || str_starts_with($keywords, '{'))) {
            try {
                $decoded = json_decode($keywords, true);
                if (is_array($decoded)) {
                    return implode(', ', $decoded);
                }
            } catch (\Exception $e) {
                // Не JSON, оставляем как есть
            }
        }
        
        // Если это массив
        if (is_array($keywords)) {
            return implode(', ', $keywords);
        }
        
        // Простая строка
        return $keywords;
    }
    
    private function showStats()
    {
        $this->info("\n📊 Статистика после индексации:");
        
        try {
            $stats = DB::table('documents')
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN search_indexed = true THEN 1 ELSE 0 END) as indexed,
                    SUM(CASE WHEN is_parsed = true THEN 1 ELSE 0 END) as parsed,
                    SUM(CASE WHEN status = "processed" THEN 1 ELSE 0 END) as processed,
                    SUM(CASE WHEN status = "processing" THEN 1 ELSE 0 END) as processing
                ')
                ->first();
            
            $this->table(
                ['Метрика', 'Значение', 'Процент'],
                [
                    ['Всего', $stats->total, '100%'],
                    ['Индексировано', $stats->indexed, round(($stats->indexed/$stats->total)*100, 1) . '%'],
                    ['Обработано', $stats->processed, round(($stats->processed/$stats->total)*100, 1) . '%'],
                ]
            );
            
            // Секции
            $sections = DB::table('documents')
                ->select('detected_section', DB::raw('COUNT(*) as count'))
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
                $this->table(['Секция', 'Кол-во'], $data);
            }
            
            // Примеры
            $this->info("\n📄 Примеры документов:");
            $examples = DB::table('documents')
                ->select('id', 'title', 'status', 'detected_section')
                ->limit(3)
                ->get();
            
            foreach ($examples as $doc) {
                $statusIcon = $doc->status === 'processed' ? '✅' : '❌';
                echo "{$statusIcon} #{$doc->id}: {$doc->title}";
                if ($doc->detected_section) {
                    echo " [{$doc->detected_section}]";
                }
                echo "\n";
            }
            
        } catch (\Exception $e) {
            $this->error("Ошибка при получении статистики: " . $e->getMessage());
        }
    }
}