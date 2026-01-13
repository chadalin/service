<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('diagnostic_rules')) {
            Schema::create('diagnostic_rules', function (Blueprint $table) {
                $table->id();
                
                // Внешние ключи как bigInteger без немедленного constraint
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
            });
            
            // Добавим внешние ключи отдельно
            $this->addForeignKeys();
        }
    }

    private function addForeignKeys(): void
    {
        // Для symptom_id
        if (Schema::hasTable('diagnostic_symptoms')) {
            Schema::table('diagnostic_rules', function (Blueprint $table) {
                $table->foreign('symptom_id')
                      ->references('id')
                      ->on('diagnostic_symptoms')
                      ->onDelete('cascade');
            });
        }
        
        // Для brand_id - используем прямой SQL для совместимости
        if (Schema::hasTable('brands')) {
            // Получим тип колонки id в brands
            $result = DB::selectOne("
                SELECT DATA_TYPE, COLUMN_TYPE 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'brands' 
                AND COLUMN_NAME = 'id'
            ");
            
            $this->info("Brands id type: " . print_r($result, true));
            
            // Добавим foreign key
            DB::statement("
                ALTER TABLE diagnostic_rules 
                ADD CONSTRAINT diagnostic_rules_brand_id_foreign 
                FOREIGN KEY (brand_id) 
                REFERENCES brands(id) 
                ON DELETE CASCADE
            ");
        }
        
        // Для model_id
        if (Schema::hasTable('car_models')) {
            Schema::table('diagnostic_rules', function (Blueprint $table) {
                $table->foreign('model_id')
                      ->references('id')
                      ->on('car_models')
                      ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        // Удаляем foreign keys
        Schema::table('diagnostic_rules', function (Blueprint $table) {
            $table->dropForeign(['symptom_id']);
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['model_id']);
        });
        
        Schema::dropIfExists('diagnostic_rules');
    }
};