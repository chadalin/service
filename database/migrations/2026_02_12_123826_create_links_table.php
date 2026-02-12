<?php
// database/migrations/2024_01_01_000000_create_links_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('url');
            $table->text('description')->nullable();
            $table->string('login')->nullable();
            $table->string('password')->nullable();
            $table->enum('auth_type', ['basic', 'form', 'none'])->default('basic');
            $table->json('additional_data')->nullable(); // Для хранения extra полей
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('links');
    }
};