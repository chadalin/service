<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ForceIndexDocuments extends Command
{
    protected $signature = 'documents:force-index';
    protected $description = 'Принудительная индексация документов прямым SQL';

    public function handle()
    {
        $this->info('🚀 Запуск принудительной индексации...');
        
        // 1. Покажем текущее состояние
        $this->showCurrentState();
        
        if (!$this->confirm('Продолжить индексацию?')) {
            return 0;
        }
        
        // 2. Выполняем прямое SQL обновление
        $this->performDirectUpdate();
        
        // 3. Покажем результат
        $this->showResult();
        
        return 0;
    }
    
    private function showCurrentState()
    {
        $this->info('📊 Текущее состояние:');
        
        $docs = DB::table('documents')
            ->select('id', 'title', 'status', 'search_indexed', 'detected_section')
            ->get();
            
        $this->table(
            ['ID', 'Title', 'Status', 'Indexed', 'Section'],
            $docs->map(function($doc) {
                return [
                    $doc->id,
                    mb_substr($doc->title, 0, 30, 'UTF-8'),
                    $doc->status,
                    $doc->search_indexed ? '✅' : '❌',
                    $doc->detected_section ?? 'NULL'
                ];
            })->toArray()
        );
    }
    
    private function performDirectUpdate()
    {
        $this->info('🔄 Выполняем обновление...');
        
        // Шаг 1: Обновляем базовые поля (БЕЗ подзапроса!)
        DB::statement("
            UPDATE documents 
            SET 
                status = 'processed',
                search_indexed = 1,
                is_parsed = CASE 
                    WHEN content_text IS NOT NULL AND LENGTH(content_text) > 0 THEN 1 
                    ELSE 0 
                END,
                parsing_quality = CASE 
                    WHEN content_text IS NOT NULL AND LENGTH(content_text) > 100 THEN 0.8 
                    ELSE 0.3 
                END,
                detected_section = 'общее',
                detected_system = 'общая информация',
                detected_component = 'основной компонент',
                keywords_text = COALESCE(keywords, 'руководство, документация'),
                updated_at = NOW()
        ");
        
        $this->info('✅ Шаг 1: Базовые поля обновлены');
        
        // Шаг 2: Определяем секции по заголовкам
        $this->updateSectionsFromTitles();
        
        // Шаг 3: Определяем системы по секциям
        $this->updateSystemsFromSections();
        
        $this->info('🎉 Все обновления выполнены!');
    }
    
    private function updateSectionsFromTitles()
    {
        $this->info('🔍 Анализируем заголовки для определения секций...');
        
        // Список паттернов для секций
        $patterns = [
            ['двигатель', "LOWER(title) LIKE '%двигатель%' OR LOWER(title) LIKE '%мотор%' OR LOWER(title) LIKE '%engine%'"],
            ['трансмиссия', "LOWER(title) LIKE '%трансмиссия%' OR LOWER(title) LIKE '%коробка%' OR LOWER(title) LIKE '%transmission%'"],
            ['тормоза', "LOWER(title) LIKE '%тормоз%' OR LOWER(title) LIKE '%brake%'"],
            ['электрика', "LOWER(title) LIKE '%электрик%' OR LOWER(title) LIKE '%electr%'"],
            ['подвеска', "LOWER(title) LIKE '%подвеск%' OR LOWER(title) LIKE '%suspension%'"],
            ['кузов', "LOWER(title) LIKE '%кузов%' OR LOWER(title) LIKE '%body%'"],
            ['рулевое', "LOWER(title) LIKE '%рулев%' OR LOWER(title) LIKE '%steering%'"],
        ];
        
        foreach ($patterns as $pattern) {
            list($section, $condition) = $pattern;
            
            $affected = DB::update("
                UPDATE documents 
                SET detected_section = ?
                WHERE ({$condition}) AND (detected_section IS NULL OR detected_section = 'общее')
            ", [$section]);
            
            if ($affected > 0) {
                $this->info("   → Назначено '{$section}': {$affected} документов");
            }
        }
    }
    
    private function updateSystemsFromSections()
    {
        $this->info('🔧 Назначаем системы по секциям...');
        
        $mappings = [
            ['двигатель', 'силовая установка'],
            ['трансмиссия', 'трансмиссия'],
            ['тормоза', 'тормозная система'],
            ['электрика', 'электрооборудование'],
            ['подвеска', 'ходовая часть'],
            ['кузов', 'кузов и элементы'],
            ['рулевое', 'рулевое управление'],
        ];
        
        foreach ($mappings as $mapping) {
            list($section, $system) = $mapping;
            
            $affected = DB::update("
                UPDATE documents 
                SET detected_system = ?
                WHERE detected_section = ? AND (detected_system IS NULL OR detected_system = 'общая информация')
            ", [$system, $section]);
            
            if ($affected > 0) {
                $this->info("   → Секция '{$section}' → система '{$system}': {$affected} документов");
            }
        }
    }
    
    private function showResult()
    {
        $this->info("\n📊 Результат индексации:");
        
        $stats = DB::table('documents')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "processed" THEN 1 ELSE 0 END) as processed,
                SUM(CASE WHEN search_indexed = 1 THEN 1 ELSE 0 END) as indexed,
                SUM(CASE WHEN detected_section != "общее" THEN 1 ELSE 0 END) as sections_detected,
                SUM(CASE WHEN is_parsed = 1 THEN 1 ELSE 0 END) as parsed
            ')
            ->first();
        
        $this->table(
            ['Метрика', 'Значение', 'Процент'],
            [
                ['Всего документов', $stats->total, '100%'],
                ['Обработано', $stats->processed, round(($stats->processed/$stats->total)*100) . '%'],
                ['Индексировано', $stats->indexed, round(($stats->indexed/$stats->total)*100) . '%'],
                ['Секции определены', $stats->sections_detected, round(($stats->sections_detected/$stats->total)*100) . '%'],
                ['Распарсено', $stats->parsed, round(($stats->parsed/$stats->total)*100) . '%'],
            ]
        );
        
        // Покажем все документы
        $docs = DB::table('documents')
            ->select('id', 'title', 'status', 'search_indexed', 'is_parsed', 'detected_section', 'detected_system', 'keywords_text')
            ->get();
            
        $this->info("\n📄 Детальная информация:");
        foreach ($docs as $doc) {
            echo "ID: {$doc->id}\n";
            echo "  Title: " . mb_substr($doc->title, 0, 40, 'UTF-8') . "...\n";
            echo "  Status: {$doc->status}\n";
            echo "  Indexed: " . ($doc->search_indexed ? '✅' : '❌') . "\n";
            echo "  Parsed: " . ($doc->is_parsed ? '✅' : '❌') . "\n";
            echo "  Section: {$doc->detected_section}\n";
            echo "  System: {$doc->detected_system}\n";
            echo "  Keywords: " . (mb_substr($doc->keywords_text ?? '', 0, 50, 'UTF-8') . '...') . "\n";
            echo "---\n";
        }
    }
}