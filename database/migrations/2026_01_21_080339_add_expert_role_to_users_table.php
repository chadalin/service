<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Добавляем роль 'expert' в enum если её нет
        Schema::table('users', function (Blueprint $table) {
            // Изменяем enum поле role
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user', 'expert') NOT NULL DEFAULT 'user'");
        });
        
        // Добавляем дополнительные поля для экспертов (опционально)
        Schema::table('users', function (Blueprint $table) {
            $table->text('expert_bio')->nullable()->after('role');
            $table->string('expert_specialization')->nullable()->after('expert_bio');
            $table->integer('expert_experience_years')->default(0)->after('expert_specialization');
            $table->decimal('expert_rating', 3, 2)->default(0)->after('expert_experience_years');
            $table->boolean('expert_is_available')->default(true)->after('expert_rating');
            $table->json('expert_schedule')->nullable()->after('expert_is_available');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Возвращаем обратно к двум ролям
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user') NOT NULL DEFAULT 'user'");
            
            // Удаляем добавленные поля
            $table->dropColumn([
                'expert_bio',
                'expert_specialization',
                'expert_experience_years',
                'expert_rating',
                'expert_is_available',
                'expert_schedule'
            ]);
        });
    }
};