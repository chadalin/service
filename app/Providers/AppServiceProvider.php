<?php

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Регистрируем функцию для использования в шаблонах
        \Illuminate\Support\Facades\Blade::directive('getCaseSymptoms', function ($expression) {
            return "<?php echo getCaseSymptoms($expression); ?>";
        });
    }
}