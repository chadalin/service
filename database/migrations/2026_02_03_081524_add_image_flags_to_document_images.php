<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('document_images', function (Blueprint $table) {
            // Сначала добавим screenshot_url если его нет
            if (!Schema::hasColumn('document_images', 'screenshot_url')) {
                $table->string('screenshot_url')->nullable()->after('thumbnail_url');
            }
            
            // Затем screenshot_path если его нет
            if (!Schema::hasColumn('document_images', 'screenshot_path')) {
                $table->string('screenshot_path')->nullable()->after('thumbnail_url');
            }
            
            // Затем screenshot_size
            if (!Schema::hasColumn('document_images', 'screenshot_size')) {
                $table->integer('screenshot_size')->nullable()->after('size');
            }
            
            // Затем thumbnail_size если его нет
            if (!Schema::hasColumn('document_images', 'thumbnail_size')) {
                $table->integer('thumbnail_size')->nullable()->after('screenshot_size');
            }
            
            // Теперь добавляем флаги ПОСЛЕ screenshot_url
            if (!Schema::hasColumn('document_images', 'has_thumbnail')) {
                $table->boolean('has_thumbnail')->default(false)->after('screenshot_url');
            }
            
            if (!Schema::hasColumn('document_images', 'has_screenshot')) {
                $table->boolean('has_screenshot')->default(false)->after('has_thumbnail');
            }
            
            if (!Schema::hasColumn('document_images', 'is_trimmed')) {
                $table->boolean('is_trimmed')->default(false)->after('has_screenshot');
            }
            
            if (!Schema::hasColumn('document_images', 'trim_info')) {
                $table->json('trim_info')->nullable()->after('is_trimmed');
            }
            
            // Также может не хватать original_filename
            if (!Schema::hasColumn('document_images', 'original_filename')) {
                $table->string('original_filename')->nullable()->after('filename');
            }
            
            // И analysis_info
            if (!Schema::hasColumn('document_images', 'analysis_info')) {
                $table->json('analysis_info')->nullable()->after('trim_info');
            }
        });
    }

    public function down()
    {
        Schema::table('document_images', function (Blueprint $table) {
            // Удаляем только те колонки, которые мы добавили
            $columns = [
                'screenshot_url',
                'screenshot_path', 
                'screenshot_size',
                'thumbnail_size',
                'has_thumbnail',
                'has_screenshot', 
                'is_trimmed',
                'trim_info',
                'original_filename',
                'analysis_info'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('document_images', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};