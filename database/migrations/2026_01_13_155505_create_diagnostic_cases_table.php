<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('diagnostic_cases')) {
            Schema::create('diagnostic_cases', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->foreignId('rule_id')->constrained('diagnostic_rules')->onDelete('cascade');
                $table->foreignId('brand_id')->constrained('brands')->onDelete('cascade');
                $table->foreignId('model_id')->nullable()->constrained('car_models')->onDelete('cascade');
                
                $table->string('engine_type')->nullable();
                $table->integer('year')->nullable();
                $table->string('vin')->nullable();
                $table->integer('mileage')->nullable();
                
                $table->json('symptoms');
                $table->text('description')->nullable();
                
                $table->json('uploaded_files')->nullable();
                
                $table->enum('status', [
                    'draft', 
                    'analyzing', 
                    'report_ready', 
                    'consultation_pending', 
                    'consultation_in_progress', 
                    'completed', 
                    'archived'
                ])->default('draft');
                
                $table->integer('step')->default(1);
                
                $table->json('analysis_result')->nullable();
                $table->decimal('price_estimate', 10, 2)->nullable();
                $table->integer('time_estimate')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_cases');
    }
};