<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin_code')->nullable()->after('password');
            $table->timestamp('pin_expires_at')->nullable()->after('pin_code');
            $table->string('company_name')->nullable()->after('pin_expires_at');
            $table->enum('role', ['admin', 'user'])->default('user')->after('company_name');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('role');
            
            // Делаем password nullable для PIN-авторизации
            $table->string('password')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pin_code', 'pin_expires_at', 'company_name', 'role', 'status']);
            $table->string('password')->nullable(false)->change();
        });
    }
};