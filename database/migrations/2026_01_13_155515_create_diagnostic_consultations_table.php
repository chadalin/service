<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('diagnostic_consultations')) {
            Schema::create('diagnostic_consultations', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('case_id')->constrained('diagnostic_cases')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('expert_id')->nullable()->constrained('users')->onDelete('cascade');
                
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
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_consultations');
    }
};