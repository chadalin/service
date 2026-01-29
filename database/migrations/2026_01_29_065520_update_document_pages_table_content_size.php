<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Для MySQL/MariaDB
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE document_pages MODIFY content LONGTEXT');
            DB::statement('ALTER TABLE document_pages MODIFY content_text LONGTEXT');
        }
        
        // Для PostgreSQL
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE document_pages ALTER COLUMN content TYPE TEXT');
            DB::statement('ALTER TABLE document_pages ALTER COLUMN content_text TYPE TEXT');
        }
        
        // Для SQLite
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite не поддерживает изменение типа столбца напрямую
            // Нужно создать новую таблицу
            Schema::table('document_pages', function (Blueprint $table) {
                $table->text('content_new')->nullable();
                $table->text('content_text_new')->nullable();
            });
            
            DB::statement('UPDATE document_pages SET content_new = content, content_text_new = content_text');
            
            Schema::table('document_pages', function (Blueprint $table) {
                $table->dropColumn('content');
                $table->dropColumn('content_text');
            });
            
            Schema::table('document_pages', function (Blueprint $table) {
                $table->renameColumn('content_new', 'content');
                $table->renameColumn('content_text_new', 'content_text');
            });
        }
    }

    public function down()
    {
        // Откат изменений
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE document_pages MODIFY content TEXT');
            DB::statement('ALTER TABLE document_pages MODIFY content_text TEXT');
        }
        
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE document_pages ALTER COLUMN content TYPE VARCHAR(65535)');
            DB::statement('ALTER TABLE document_pages ALTER COLUMN content_text TYPE VARCHAR(65535)');
        }
    }
};