<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('consultation_orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->enum('consultation_type', ['basic', 'premium', 'expert']);
        $table->foreignId('rule_id')->nullable()->constrained('diagnostic_rules');
        $table->foreignId('case_id')->nullable()->constrained('diagnostic_cases');
        $table->foreignId('brand_id')->constrained('brands');
        $table->foreignId('model_id')->nullable()->constrained('car_models');
        $table->json('symptoms')->nullable();
        $table->text('symptom_description')->nullable();
        $table->text('additional_info')->nullable();
        $table->json('uploaded_files')->nullable();
        $table->string('contact_name');
        $table->string('contact_phone');
        $table->string('contact_email');
        $table->integer('year')->nullable();
        $table->string('engine_type')->nullable();
        $table->integer('mileage')->nullable();
        $table->text('description')->nullable();
        $table->decimal('price', 10, 2);
        $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_orders');
    }
};
