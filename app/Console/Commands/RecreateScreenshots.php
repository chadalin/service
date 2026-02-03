<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DocumentImage;
use App\Services\ScreenshotService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class RecreateScreenshots extends Command
{
    protected $signature = 'screenshots:recreate {documentId?} {--all} {--simple}';
    protected $description = 'Пересоздать скриншоты для изображений';
    
    protected $screenshotService;
    
    public function __construct()
    {
        parent::__construct();
        $this->screenshotService = new ScreenshotService();
    }

    public function handle()
    {
        $query = DocumentImage::query();
        
        if ($this->option('all')) {
            $this->info("Пересоздание скриншотов для всех документов");
        } elseif ($this->argument('documentId')) {
            $documentId = $this->argument('documentId');
            $query->where('document_id', $documentId);
            $this->info("Пересоздание скриншотов для документа: {$documentId}");
        } else {
            $this->error("Укажите ID документа или используйте --all");
            return;
        }
        
        $images = $query->get();
        $this->info("Найдено изображений: " . $images->count());
        
        $bar = $this->output->createProgressBar($images->count());
        $bar->start();
        
        $created = 0;
        $failed = 0;
        $skipped = 0;
        
        foreach ($images as $image) {
            // Пропускаем если не нужно создавать скриншот
            if (!$this->screenshotService->needsScreenshot($image->path)) {
                $skipped++;
                $bar->advance();
                continue;
            }
            
            // Проверяем существование оригинала
            if (!Storage::disk('public')->exists($image->path)) {
                Log::warning("Оригинал не найден: {$image->path}");
                $failed++;
                $bar->advance();
                continue;
            }
            
            // Определяем путь для скриншота
            $screenshotsDir = "document_images/screenshots/{$image->document_id}";
            Storage::disk('public')->makeDirectory($screenshotsDir, 0755, true);
            
            $screenshotName = "screen_img_{$image->id}.jpg";
            $screenshotPath = $screenshotsDir . '/' . $screenshotName;
            
            // Удаляем старый скриншот если есть
            if ($image->screenshot_path && Storage::disk('public')->exists($image->screenshot_path)) {
                Storage::disk('public')->delete($image->screenshot_path);
            }
            
            // Создаем новый скриншот
            if ($this->option('simple')) {
                $success = $this->screenshotService->createSimpleScreenshot($image->path, $screenshotPath);
            } else {
                $success = $this->screenshotService->createOptimizedScreenshot($image->path, $screenshotPath);
            }
            
            if ($success && Storage::disk('public')->exists($screenshotPath)) {
                $image->screenshot_path = $screenshotPath;
                $image->screenshot_url = Storage::url($screenshotPath);
                $image->has_screenshot = true;
                $image->screenshot_size = Storage::disk('public')->size($screenshotPath);
                $image->save();
                $created++;
                
                $this->info("\n✅ Создан скриншот для изображения {$image->id}");
            } else {
                $failed++;
                $this->error("\n❌ Не удалось создать скриншот для изображения {$image->id}");
                
                // Пробуем создать простой скриншот
                if (!$this->option('simple')) {
                    $this->warn("Пробуем создать простой скриншот...");
                    $simpleSuccess = $this->screenshotService->createSimpleScreenshot($image->path, $screenshotPath);
                    
                    if ($simpleSuccess) {
                        $image->screenshot_path = $screenshotPath;
                        $image->screenshot_url = Storage::url($screenshotPath);
                        $image->has_screenshot = true;
                        $image->screenshot_size = Storage::disk('public')->size($screenshotPath);
                        $image->save();
                        $created++;
                        $this->info("✅ Простой скриншот создан");
                    }
                }
            }
            
            $bar->advance();
            
            // Пауза чтобы не перегружать систему
            usleep(100000); // 0.1 секунда
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Готово!");
        $this->table(['Статус', 'Количество'], [
            ['Создано', $created],
            ['Пропущено', $skipped],
            ['Не удалось', $failed],
            ['Всего', $images->count()],
        ]);
    }
}