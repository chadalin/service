<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('price_items', function (Blueprint $table) {
            $table->id();
            $table->string('brand_id', 255)->nullable()->comment('Бренд каталога (varchar чтобы соответствовать brands.id)');
            $table->string('catalog_brand', 100)->nullable()->comment('Бренд из каталога (оригинальный)');
            $table->string('sku', 255)->comment('Артикул (уникальный)');
            $table->string('name')->comment('Название запчасти');
            $table->integer('quantity')->default(0)->comment('Количество');
            $table->decimal('price', 15, 2)->default(0)->comment('Цена');
            $table->string('unit', 50)->nullable()->comment('Единица измерения');
            $table->text('description')->nullable()->comment('Описание');
            $table->json('compatibility')->nullable()->comment('Совместимость (JSON)');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Индексы
            $table->index('brand_id');
            $table->index('catalog_brand');
            $table->unique('sku'); // SKU должен быть уникальным
            $table->index('name');
            $table->index('price');
            $table->index(['brand_id', 'sku']);
            $table->index(['catalog_brand', 'sku']);
        });

        // Таблица для связи прайса с диагностическими симптомами
        Schema::create('price_symptom_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('price_item_id');
            $table->unsignedBigInteger('symptom_id');
            $table->decimal('match_score', 5, 2)->default(0)->comment('Степень совпадения');
            $table->string('match_type', 50)->comment('Тип совпадения');
            $table->timestamps();

            // Индексы
            $table->index('price_item_id');
            $table->index('symptom_id');
            $table->index('match_score');
            $table->unique(['price_item_id', 'symptom_id']);

            // Внешние ключи для связей между таблицами
            $table->foreign('price_item_id')
                  ->references('id')
                  ->on('price_items')
                  ->onDelete('cascade');
                  
            $table->foreign('symptom_id')
                  ->references('id')
                  ->on('diagnostic_symptoms')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('price_symptom_matches');
        Schema::dropIfExists('price_items');
    }
};