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
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'search_indexed')) {
                $table->boolean('search_indexed')->default(false)->after('keywords');
            }
            
            if (!Schema::hasColumn('documents', 'is_parsed')) {
                $table->boolean('is_parsed')->default(false)->after('search_indexed');
            }
            
            if (!Schema::hasColumn('documents', 'detected_section')) {
                $table->string('detected_section', 100)->nullable()->after('is_parsed');
            }
            
            if (!Schema::hasColumn('documents', 'detected_system')) {
                $table->string('detected_system', 100)->nullable()->after('detected_section');
            }
            
            if (!Schema::hasColumn('documents', 'detected_component')) {
                $table->string('detected_component', 100)->nullable()->after('detected_system');
            }
            
            if (!Schema::hasColumn('documents', 'search_count')) {
                $table->integer('search_count')->default(0)->after('detected_component');
            }
            
            if (!Schema::hasColumn('documents', 'view_count')) {
                $table->integer('view_count')->default(0)->after('search_count');
            }
            
            if (!Schema::hasColumn('documents', 'average_relevance')) {
                $table->decimal('average_relevance', 3, 2)->nullable()->after('view_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $columns = [
                'search_indexed',
                'is_parsed',
                'detected_section',
                'detected_system',
                'detected_component',
                'search_count',
                'view_count',
                'average_relevance',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('documents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};