<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('scanner_logs')) {
            Schema::create('scanner_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('case_id')->constrained('diagnostic_cases')->cascadeOnDelete();
                $table->string('scanner_type');
                $table->string('file_name');
                $table->string('file_path');
                $table->json('parsed_data')->nullable();
                $table->json('error_codes')->nullable();
                $table->json('live_data')->nullable();
                $table->text('raw_content')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('scanner_logs');
    }
};