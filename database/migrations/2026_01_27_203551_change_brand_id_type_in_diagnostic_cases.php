<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    // Меняем тип на string для совместимости с таблицей brands
    Schema::table('diagnostic_cases', function (Blueprint $table) {
        $table->string('brand_id', 50)->change();
        $table->string('model_id', 50)->change()->nullable();
        
        // Обновляем foreign key если нужно
        // $table->dropForeign(['brand_id']);
        // $table->foreign('brand_id')->references('id')->on('brands');
    });
}

public function down()
{
    Schema::table('diagnostic_cases', function (Blueprint $table) {
        $table->unsignedBigInteger('brand_id')->change();
        $table->unsignedBigInteger('model_id')->change()->nullable();
    });
}
};
