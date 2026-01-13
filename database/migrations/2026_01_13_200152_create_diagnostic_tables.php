<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Отключаем проверку foreign keys
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // 1. Таблица симптомов
        if (!Schema::hasTable('diagnostic_symptoms')) {
            Schema::create('diagnostic_symptoms', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->json('related_systems')->nullable();
                $table->string('image')->nullable();
                $table->integer('frequency')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
            
            echo "Created diagnostic_symptoms table\n";
        }
        
        // 2. Таблица правил (БЕЗ внешних ключей сначала)
        if (!Schema::hasTable('diagnostic_rules')) {
            Schema::create('diagnostic_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('symptom_id');
                $table->unsignedBigInteger('brand_id');
                $table->unsignedBigInteger('model_id')->nullable();
                
                $table->json('conditions');
                $table->json('possible_causes');
                $table->json('required_data');
                $table->json('diagnostic_steps');
                $table->integer('complexity_level')->default(1);
                $table->integer('estimated_time')->nullable();
                $table->decimal('base_consultation_price', 10, 2)->default(3000);
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                
                // Индексы
                $table->index('symptom_id');
                $table->index('brand_id');
                $table->index('model_id');
                $table->index('is_active');
            });
            
            echo "Created diagnostic_rules table\n";
        }
        
        // 3. Таблица кейсов
        if (!Schema::hasTable('diagnostic_cases')) {
            Schema::create('diagnostic_cases', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('rule_id');
                $table->unsignedBigInteger('brand_id');
                $table->unsignedBigInteger('model_id')->nullable();
                
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
                
                // Индексы
                $table->index('user_id');
                $table->index('rule_id');
                $table->index('brand_id');
                $table->index('model_id');
                $table->index('status');
                $table->index('created_at');
            });
            
            echo "Created diagnostic_cases table\n";
        }
        
        // 4. Таблица консультаций
        if (!Schema::hasTable('diagnostic_consultations')) {
            Schema::create('diagnostic_consultations', function (Blueprint $table) {
                $table->id();
                $table->uuid('case_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('expert_id')->nullable();
                
                $table->enum('type', ['basic', 'premium', 'expert'])->default('basic');
                $table->decimal('price', 10, 2);
                $table->enum('status', [
                    'pending', 
                    'scheduled', 
                    'in_progress', 
                    'completed', 
                    'cancelled'
                ])->default('pending');
                
                $table->timestamp('scheduled_at')->nullable();
                $table->integer('duration')->nullable();
                
                $table->string('payment_id')->nullable();
                $table->enum('payment_status', [
                    'pending', 
                    'paid', 
                    'failed', 
                    'refunded'
                ])->default('pending');
                
                $table->timestamp('paid_at')->nullable();
                
                $table->text('expert_notes')->nullable();
                $table->text('customer_feedback')->nullable();
                $table->integer('rating')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
                
                // Индексы
                $table->index('case_id');
                $table->index('user_id');
                $table->index('expert_id');
                $table->index('status');
                $table->index('payment_status');
            });
            
            echo "Created diagnostic_consultations table\n";
        }
        
        // 5. Таблица отчётов
        if (!Schema::hasTable('diagnostic_reports')) {
            Schema::create('diagnostic_reports', function (Blueprint $table) {
                $table->id();
                $table->uuid('case_id');
                $table->unsignedBigInteger('consultation_id')->nullable();
                
                $table->enum('report_type', ['free', 'premium', 'expert'])->default('free');
                
                $table->json('summary');
                $table->json('possible_causes');
                $table->json('diagnostic_plan');
                $table->json('estimated_costs');
                $table->json('recommended_actions');
                $table->json('parts_list')->nullable();
                
                $table->boolean('is_white_label')->default(false);
                $table->string('partner_name')->nullable();
                $table->json('partner_contacts')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
                
                // Индексы
                $table->index('case_id');
                $table->index('consultation_id');
                $table->index('report_type');
            });
            
            echo "Created diagnostic_reports table\n";
        }
        
        // 6. Таблица логов сканера
        if (!Schema::hasTable('scanner_logs')) {
            Schema::create('scanner_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('case_id');
                $table->string('scanner_type');
                $table->string('file_name');
                $table->string('file_path');
                $table->json('parsed_data')->nullable();
                $table->json('error_codes')->nullable();
                $table->json('live_data')->nullable();
                $table->text('raw_content')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                // Индексы
                $table->index('case_id');
                $table->index('scanner_type');
            });
            
            echo "Created scanner_logs table\n";
        }
        
        // Включаем проверку foreign keys
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Отключаем проверку foreign keys
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Удаляем в обратном порядке
        Schema::dropIfExists('scanner_logs');
        Schema::dropIfExists('diagnostic_reports');
        Schema::dropIfExists('diagnostic_consultations');
        Schema::dropIfExists('diagnostic_cases');
        Schema::dropIfExists('diagnostic_rules');
        Schema::dropIfExists('diagnostic_symptoms');
        
        // Включаем проверку foreign keys
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};