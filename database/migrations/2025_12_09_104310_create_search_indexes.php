<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Добавляем полнотекстовый индекс к documents
        Schema::table('documents', function (Blueprint $table) {
            // Убедимся что колонки существуют
            if (!Schema::hasColumn('documents', 'content_text')) {
                $table->text('content_text')->nullable()->after('title');
            }
            
            if (!Schema::hasColumn('documents', 'search_vector')) {
                $table->text('search_vector')->nullable()->after('content_text');
            }
            
            if (!Schema::hasColumn('documents', 'keywords')) {
                $table->json('keywords')->nullable()->after('search_vector');
            }
        });

        // Для MySQL создаем полнотекстовый индекс
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE documents ADD FULLTEXT fulltext_index (title, content_text)');
        }

        // Для SQLite или других баз можно создать обычные индексы
        Schema::table('documents', function (Blueprint $table) {
            $table->index(['title']);
            $table->index(['status']);
        });

        // Создаем таблицу для хранения статистики поиска
        Schema::create('search_stats', function (Blueprint $table) {
            $table->id();
            $table->string('query');
            $table->integer('results_count')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('car_model_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['query']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('search_stats');
        
        Schema::table('documents', function (Blueprint $table) {
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE documents DROP INDEX fulltext_index');
            }
            
            $table->dropColumn(['search_vector', 'keywords']);
            $table->dropIndex(['title']);
            $table->dropIndex(['status']);
        });
    }
};