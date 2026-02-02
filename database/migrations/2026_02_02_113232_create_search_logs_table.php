<?php
// database/migrations/2024_01_01_000002_create_search_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSearchLogsTable extends Migration
{
    public function up()
    {
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('query', 500);
            $table->json('filters')->nullable();
            $table->integer('results_count')->default(0);
            $table->float('response_time', 8, 6);
            
            // Информация о пользователе
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            
            // Информация о поиске
            $table->string('search_type', 50)->default('standard');
            $table->json('search_meta')->nullable();
            
            // Статистика
            $table->integer('clicked_result_id')->nullable();
            $table->timestamp('clicked_at')->nullable();
            
            $table->timestamps();
            
            // Индексы
            $table->index(['query'], 'search_logs_query_idx');
            $table->index(['user_id'], 'search_logs_user_idx');
            $table->index(['created_at'], 'search_logs_created_at_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('search_logs');
    }
}