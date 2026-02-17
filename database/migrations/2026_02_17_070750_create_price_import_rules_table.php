<?php
// database/migrations/2026_02_17_070750_create_price_import_rules_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePriceImportRulesTable extends Migration
{
    public function up()
    {
        Schema::create('price_import_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('email_account_id')
                  ->constrained()
                  ->onDelete('cascade');
            
            // Используем string для brand_id, так как в таблице brands id - varchar
            $table->string('brand_id');
            
            $table->string('email_subject_pattern')->nullable();
            $table->string('email_sender_pattern')->nullable();
            $table->json('filename_patterns')->nullable();
            $table->boolean('update_existing')->default(true);
            $table->boolean('match_symptoms')->default(false);
            $table->json('column_mapping')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_processed_at')->nullable();
            $table->timestamps();
            
            // Внешний ключ для строкового ID
            $table->foreign('brand_id')
                  ->references('id')
                  ->on('brands')
                  ->onDelete('cascade');
            
            // Индексы для ускорения поиска
            $table->index('brand_id');
            $table->index('email_account_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('price_import_rules');
    }
}