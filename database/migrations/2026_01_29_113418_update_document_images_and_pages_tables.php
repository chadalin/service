<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Обновляем таблицу document_images
        Schema::table('document_images', function (Blueprint $table) {
            if (!Schema::hasColumn('document_images', 'thumbnail_path')) {
                $table->string('thumbnail_path')->nullable()->after('url');
            }
            if (!Schema::hasColumn('document_images', 'thumbnail_url')) {
                $table->string('thumbnail_url')->nullable()->after('thumbnail_path');
            }
            if (!Schema::hasColumn('document_images', 'original_width')) {
                $table->integer('original_width')->nullable()->after('height');
            }
            if (!Schema::hasColumn('document_images', 'original_height')) {
                $table->integer('original_height')->nullable()->after('original_width');
            }
            if (!Schema::hasColumn('document_images', 'thumbnail_size')) {
                $table->integer('thumbnail_size')->nullable()->after('size');
            }
            if (!Schema::hasColumn('document_images', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('description');
            }
            if (!Schema::hasColumn('document_images', 'extension')) {
                $table->string('extension', 10)->nullable()->after('mime_type');
            }
        });
        
        // Обновляем таблицу document_pages
        Schema::table('document_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('document_pages', 'has_images')) {
                $table->boolean('has_images')->default(false)->after('metadata');
            }
        });
        
        // Обновляем таблицу documents (если нужно)
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'parsing_progress')) {
                $table->decimal('parsing_progress', 5, 2)->default(0)->after('parsing_quality');
            }
            if (!Schema::hasColumn('documents', 'processing_started_at')) {
                $table->timestamp('processing_started_at')->nullable()->after('parsed_at');
            }
        });
    }
    
    public function down()
    {
        Schema::table('document_images', function (Blueprint $table) {
            $table->dropColumn(['thumbnail_path', 'thumbnail_url', 'original_width', 
                               'original_height', 'thumbnail_size', 'mime_type', 'extension']);
        });
        
        Schema::table('document_pages', function (Blueprint $table) {
            $table->dropColumn('has_images');
        });
        
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['parsing_progress', 'processing_started_at']);
        });
    }
};