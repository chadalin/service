<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // document_images
Schema::create('document_images', function (Blueprint $table) {
    $table->id();
    $table->foreignId('document_id')->constrained()->onDelete('cascade');
    $table->foreignId('page_id')->nullable()->constrained('document_pages')->onDelete('cascade');
    $table->integer('page_number');
    $table->string('filename');
    $table->string('path');
    $table->string('url')->nullable();
    $table->integer('width')->nullable();
    $table->integer('height')->nullable();
    $table->integer('size')->nullable();
    $table->text('description')->nullable();
    $table->text('ocr_text')->nullable();
    $table->integer('position')->default(1);
    $table->boolean('is_preview')->default(false);
    $table->string('status')->default('extracted');
    $table->timestamps();
    
    $table->index(['document_id', 'page_id']);
    $table->index(['document_id', 'page_number']);
    $table->index('path');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_images');
    }
};
