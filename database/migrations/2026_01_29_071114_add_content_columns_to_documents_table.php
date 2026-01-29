<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Проверяем существование колонок перед добавлением
        if (!Schema::hasColumn('documents', 'content')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->longText('content')->nullable()->after('title');
            });
        }
        
        if (!Schema::hasColumn('documents', 'content_text')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->longText('content_text')->nullable()->after('content');
            });
        }
        
        if (!Schema::hasColumn('documents', 'total_pages')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->integer('total_pages')->default(0)->after('content_text');
            });
        }
        
        if (!Schema::hasColumn('documents', 'word_count')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->integer('word_count')->default(0)->after('total_pages');
            });
        }
        
        if (!Schema::hasColumn('documents', 'parsing_quality')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->decimal('parsing_quality', 3, 2)->default(0)->after('word_count');
            });
        }
        
        if (!Schema::hasColumn('documents', 'parsed_at')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->timestamp('parsed_at')->nullable()->after('parsing_quality');
            });
        }
    }

    public function down()
    {
        // Откат изменений
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['content', 'content_text', 'total_pages', 'word_count', 'parsing_quality', 'parsed_at']);
        });
    }
};