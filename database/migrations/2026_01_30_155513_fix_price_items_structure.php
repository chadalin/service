<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixPriceItemsStructure extends Migration
{
    public function up()
    {
        if (Schema::hasTable('price_items')) {
            // Добавляем недостающие колонки если их нет
            if (!Schema::hasColumn('price_items', 'category')) {
                Schema::table('price_items', function (Blueprint $table) {
                    $table->string('category')->nullable();
                });
            }
            
            if (!Schema::hasColumn('price_items', 'image_url')) {
                Schema::table('price_items', function (Blueprint $table) {
                    $table->string('image_url')->nullable();
                });
            }
            
            if (!Schema::hasColumn('price_items', 'unit')) {
                Schema::table('price_items', function (Blueprint $table) {
                    $table->string('unit')->default('шт.');
                });
            }
            
            if (!Schema::hasColumn('price_items', 'min_order_qty')) {
                Schema::table('price_items', function (Blueprint $table) {
                    $table->integer('min_order_qty')->default(1);
                });
            }
            
            if (!Schema::hasColumn('price_items', 'quantity')) {
                Schema::table('price_items', function (Blueprint $table) {
                    $table->integer('quantity')->default(0);
                });
            }
        }
    }

    public function down()
    {
        // При откате удаляем добавленные колонки
        if (Schema::hasTable('price_items')) {
            Schema::table('price_items', function (Blueprint $table) {
                $columnsToDrop = ['category', 'image_url', 'unit', 'min_order_qty', 'quantity'];
                foreach ($columnsToDrop as $column) {
                    if (Schema::hasColumn('price_items', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
}