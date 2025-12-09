<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
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
    }

    public function down()
    {
        Schema::dropIfExists('documents');
    }
};