<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->after('id');
            $table->unsignedBigInteger('store_id')->nullable()->after('employee_id');
            $table->unsignedBigInteger('company_id')->nullable()->after('store_id');
            
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            
            $table->index(['company_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['store_id']);
            $table->dropForeign(['company_id']);
            $table->dropIndex(['company_id', 'role']);
            $table->dropColumn(['employee_id', 'store_id', 'company_id']);
        });
    }
};

