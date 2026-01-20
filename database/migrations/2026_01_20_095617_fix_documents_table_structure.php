<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixDocumentsTableStructure extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Используем DB::statement для полного контроля
        DB::statement('ALTER TABLE documents MODIFY content_text LONGTEXT NULL');
        DB::statement('ALTER TABLE documents MODIFY keywords LONGTEXT NULL');
        DB::statement('ALTER TABLE documents MODIFY metadata LONGTEXT NULL');
        DB::statement('ALTER TABLE documents MODIFY sections LONGTEXT NULL');
        
        // Увеличиваем размер поля status
        DB::statement('ALTER TABLE documents MODIFY status VARCHAR(50) DEFAULT "uploaded"');
        
        // Добавляем индекс для ускорения
        Schema::table('documents', function (Blueprint $table) {
            $table->index(['status', 'is_parsed', 'search_indexed']);
            $table->index(['car_model_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем обратно (опционально)
        DB::statement('ALTER TABLE documents MODIFY content_text TEXT NULL');
        DB::statement('ALTER TABLE documents MODIFY keywords TEXT NULL');
        DB::statement('ALTER TABLE documents MODIFY metadata TEXT NULL');
        DB::statement('ALTER TABLE documents MODIFY sections TEXT NULL');
        DB::statement('ALTER TABLE documents MODIFY status VARCHAR(20) DEFAULT "uploaded"');
        
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['status', 'is_parsed', 'search_indexed']);
            $table->dropIndex(['car_model_id', 'category_id']);
        });
    }
}