<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixDocumentsContentText extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Проверяем и исправляем только проблемные поля
        if (Schema::hasColumn('documents', 'content_text')) {
            DB::statement('ALTER TABLE documents MODIFY content_text LONGTEXT NULL');
            echo "Поле content_text увеличено до LONGTEXT\n";
        }
        
        if (Schema::hasColumn('documents', 'keywords')) {
            DB::statement('ALTER TABLE documents MODIFY keywords LONGTEXT NULL');
            echo "Поле keywords увеличено до LONGTEXT\n";
        }
        
        if (Schema::hasColumn('documents', 'status')) {
            DB::statement('ALTER TABLE documents MODIFY status VARCHAR(50) DEFAULT "uploaded"');
            echo "Поле status увеличено до VARCHAR(50)\n";
        }
        
        // Добавляем недостающие поля только если запрашиваются в коде
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'metadata')) {
                $table->json('metadata')->nullable()->after('keywords_text');
                echo "Добавлено поле metadata\n";
            }
            
            if (!Schema::hasColumn('documents', 'sections')) {
                $table->json('sections')->nullable()->after('metadata');
                echo "Добавлено поле sections\n";
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем назад (опционально)
        if (Schema::hasColumn('documents', 'content_text')) {
            DB::statement('ALTER TABLE documents MODIFY content_text TEXT NULL');
        }
        
        if (Schema::hasColumn('documents', 'keywords')) {
            DB::statement('ALTER TABLE documents MODIFY keywords TEXT NULL');
        }
        
        if (Schema::hasColumn('documents', 'status')) {
            DB::statement('ALTER TABLE documents MODIFY status VARCHAR(20) DEFAULT "uploaded"');
        }
        
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'metadata')) {
                $table->dropColumn('metadata');
            }
            
            if (Schema::hasColumn('documents', 'sections')) {
                $table->dropColumn('sections');
            }
        });
    }
}