<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Сначала удаляем старые таблицы если они есть (будьте осторожны!)
        Schema::dropIfExists('documents');
        Schema::dropIfExists('search_queries');
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('repair_categories');

        // Создаем таблицу brands с новой структурой
        Schema::create('brands', function (Blueprint $table) {
            $table->string('id')->primary(); // ID из CSV
            $table->string('name');
            $table->string('name_cyrillic')->nullable();
            $table->boolean('is_popular')->default(false);
            $table->string('country')->nullable();
            $table->integer('year_from')->nullable();
            $table->integer('year_to')->nullable();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Создаем таблицу car_models с новой структурой
        Schema::create('car_models', function (Blueprint $table) {
            $table->id();
            $table->string('model_id')->unique(); // ID из CSV
            $table->string('brand_id');
            $table->string('name');
            $table->string('name_cyrillic')->nullable();
            $table->string('class')->nullable();
            $table->integer('year_from')->nullable();
            $table->integer('year_to')->nullable();
            $table->string('years')->nullable();
            $table->string('engine_types')->nullable();
            $table->timestamps();

            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
        });

        // Создаем таблицу repair_categories
        Schema::create('repair_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('repair_categories')->onDelete('cascade');
            $table->timestamps();
        });

        // Создаем таблицу documents
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_model_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('repair_categories')->onDelete('cascade');
            $table->string('title');
            $table->text('content_text')->nullable();
            $table->string('original_filename');
            $table->enum('file_type', ['pdf', 'doc', 'docx', 'txt', 'html']);
            $table->string('file_path');
            $table->string('source_url')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['processing', 'processed', 'error'])->default('processing');
            $table->timestamps();

            $table->index(['car_model_id', 'category_id']);
        });

        // Создаем таблицу search_queries
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('query_text');
            $table->foreignId('car_model_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('results_count')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('search_queries');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('repair_categories');
    }
};