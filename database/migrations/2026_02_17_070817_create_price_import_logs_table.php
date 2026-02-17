<?php
// database/migrations/2026_02_17_070800_create_price_import_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePriceImportLogsTable extends Migration
{
    public function up()
    {
        Schema::create('price_import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('price_import_rule_id')->nullable()->constrained()->nullOnDelete();
            
            // Используем string для brand_id
            $table->string('brand_id')->nullable();
            
            $table->string('email_subject')->nullable();
            $table->string('email_from')->nullable();
            $table->string('filename')->nullable();
            $table->string('status'); // success, error, skipped
            $table->integer('items_processed')->default(0);
            $table->integer('items_created')->default(0);
            $table->integer('items_updated')->default(0);
            $table->integer('items_skipped')->default(0);
            $table->json('details')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            // Индексы
            $table->index('brand_id');
            $table->index('status');
            $table->index('created_at');
            
            // Внешний ключ для brand_id (опционально, может быть null)
            $table->foreign('brand_id')
                  ->references('id')
                  ->on('brands')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('price_import_logs');
    }
}