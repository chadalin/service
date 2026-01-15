<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SearchEngine;
use App\Services\SemanticSearchEngine;
use App\Services\ManualParserService;
use App\Services\HybridSearchEngine;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SearchEngine::class, function ($app) {
            return new SearchEngine();
        });
        
        $this->app->singleton(SemanticSearchEngine::class, function ($app) {
            return new SemanticSearchEngine();
        });
        
        $this->app->singleton(ManualParserService::class, function ($app) {
            return new ManualParserService();
        });
        
        $this->app->singleton(HybridSearchEngine::class, function ($app) {
            return new HybridSearchEngine(
                $app->make(SearchEngine::class),
                $app->make(SemanticSearchEngine::class)
            );
        });
        
        // Регистрируем конфигурацию
        $this->mergeConfigFrom(
            __DIR__.'/../../config/search.php', 'search'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Публикуем конфигурацию
        $this->publishes([
            __DIR__.'/../../config/search.php' => config_path('search.php'),
        ], 'config');
        
        // Загружаем команды
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\IndexDocuments::class,
                \App\Console\Commands\RebuildSearchIndex::class,
                \App\Console\Commands\ParseDocuments::class,
                \App\Console\Commands\GenerateEmbeddings::class,
            ]);
        }
    }
}