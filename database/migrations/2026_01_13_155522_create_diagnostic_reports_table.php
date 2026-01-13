<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('diagnostic_reports')) {
            Schema::create('diagnostic_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('case_id')->constrained('diagnostic_cases')->cascadeOnDelete();
                $table->foreignId('consultation_id')->nullable()->constrained('diagnostic_consultations')->cascadeOnDelete();
                
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
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_reports');
    }
};