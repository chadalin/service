<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // document_pages
    public function up()
{
Schema::create('document_pages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('document_id')->constrained()->onDelete('cascade');
    $table->integer('page_number');
    $table->text('content')->nullable();
    $table->text('content_text')->nullable();
    $table->integer('word_count')->default(0);
    $table->integer('character_count')->default(0);
    $table->integer('paragraph_count')->default(0);
    $table->integer('tables_count')->default(0);
    $table->string('section_title')->nullable();
    $table->json('metadata')->nullable();
    $table->boolean('is_preview')->default(false);
    $table->decimal('parsing_quality', 3, 2)->default(0);
    $table->string('status')->default('pending');
    $table->timestamps();
    
    $table->unique(['document_id', 'page_number']);
    $table->index(['document_id', 'is_preview']);
    $table->index(['document_id', 'section_title']);
});
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_pages');
    }
};
