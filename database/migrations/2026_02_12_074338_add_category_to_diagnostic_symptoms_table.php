<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryToDiagnosticSymptomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('diagnostic_symptoms', function (Blueprint $table) {
            // Добавляем колонку category, если её нет
            if (!Schema::hasColumn('diagnostic_symptoms', 'category')) {
                $table->string('category', 100)->nullable()->after('description')->comment('Категория симптома');
            }
            
            // Исправляем опечатку, если колонка называется create_at вместо created_at
            if (Schema::hasColumn('diagnostic_symptoms', 'create_at') && !Schema::hasColumn('diagnostic_symptoms', 'created_at')) {
                $table->renameColumn('create_at', 'created_at');
            }
            
            // Если нет колонки created_at, добавляем
            if (!Schema::hasColumn('diagnostic_symptoms', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('diagnostic_symptoms', function (Blueprint $table) {
            if (Schema::hasColumn('diagnostic_symptoms', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
}