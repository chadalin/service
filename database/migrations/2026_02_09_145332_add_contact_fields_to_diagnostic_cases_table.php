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
         Schema::table('diagnostic_cases', function (Blueprint $table) {
        $table->string('contact_name')->nullable()->after('description');
        $table->string('contact_phone')->nullable()->after('contact_name');
        $table->string('contact_email')->nullable()->after('contact_phone');
        $table->timestamp('contacted_at')->nullable()->after('contact_email');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnostic_cases', function (Blueprint $table) {

             //$table->dropColumn($column);
        });
    }
};
