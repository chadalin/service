<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Исправляем таблицу document_pages если она существует
        if (Schema::hasTable('document_pages')) {
            if (Schema::hasColumn('document_pages', 'content')) {
                DB::statement('ALTER TABLE document_pages MODIFY content LONGTEXT');
            }
            
            if (Schema::hasColumn('document_pages', 'content_text')) {
                DB::statement('ALTER TABLE document_pages MODIFY content_text LONGTEXT');
            }
        }
        
        // Увеличиваем размер полей статуса если они существуют
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'status')) {
                $table->string('status', 50)->change();
            }
        });
        
        Schema::table('document_pages', function (Blueprint $table) {
            if (Schema::hasColumn('document_pages', 'status')) {
                $table->string('status', 50)->change();
            }
        });
        
        // Создаем таблицу document_pages если она не существует
        if (!Schema::hasTable('document_pages')) {
            Schema::create('document_pages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained()->onDelete('cascade');
                $table->integer('page_number');
                $table->longText('content')->nullable();
                $table->longText('content_text')->nullable();
                $table->integer('word_count')->default(0);
                $table->integer('character_count')->default(0);
                $table->integer('paragraph_count')->default(0);
                $table->integer('tables_count')->default(0);
                $table->string('section_title')->nullable();
                $table->json('metadata')->nullable();
                $table->boolean('is_preview')->default(false);
                $table->decimal('parsing_quality', 3, 2)->default(0);
                $table->string('status', 50)->default('pending');
                $table->timestamps();
                
                $table->unique(['document_id', 'page_number']);
                $table->index(['document_id', 'is_preview']);
            });
        }
    }

    public function down()
    {
        // Откат изменений
        Schema::table('document_pages', function (Blueprint $table) {
            if (Schema::hasColumn('document_pages', 'content')) {
                $table->text('content')->nullable()->change();
            }
            if (Schema::hasColumn('document_pages', 'content_text')) {
                $table->text('content_text')->nullable()->change();
            }
        });
        
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'status')) {
                $table->string('status', 20)->change();
            }
        });
        
        Schema::table('document_pages', function (Blueprint $table) {
            if (Schema::hasColumn('document_pages', 'status')) {
                $table->string('status', 20)->change();
            }
        });
    }
};